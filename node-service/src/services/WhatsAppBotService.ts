import path from 'path';
import qrcode from 'qrcode-terminal';
import { Boom } from '@hapi/boom';
import pino from 'pino';
import makeWASocket, {
  Browsers,
  DisconnectReason,
  fetchLatestWaWebVersion,
  useMultiFileAuthState,
  type ConnectionState,
  type WASocket,
} from '@whiskeysockets/baileys';
import { ServiceUnavailableError, WhatsAppGroup } from '../types';

const AUTH_DIR = path.resolve(process.cwd(), 'auth');
const RECONNECT_DELAY_MS = 5_000;

const baileysLogger = pino({ level: 'silent' });

class WhatsAppBotService {
  private sock: WASocket | null = null;
  private isReady = false;
  private isStarting = false;
  private stopReconnect = false;
  private reconnectTimer: ReturnType<typeof setTimeout> | null = null;

  async initialize(): Promise<void> {
    if (this.isStarting) {
      return;
    }

    this.isStarting = true;
    this.stopReconnect = false;

    try {
      if (this.reconnectTimer) {
        clearTimeout(this.reconnectTimer);
        this.reconnectTimer = null;
      }

      await this.destroySocket();

      console.log('Starting WhatsApp...');

      const { state, saveCreds } = await useMultiFileAuthState(AUTH_DIR);
      const { version, isLatest } = await fetchLatestWaWebVersion();

      console.log(
        `Using WA Web version ${version.join('.')}${isLatest ? ' (latest)' : ''}`
      );

      if (!state.creds.registered) {
        console.log('Waiting for QR scan...');
      }

      const sock = makeWASocket({
        version,
        auth: state,
        browser: Browsers.macOS('Chrome'),
        logger: baileysLogger,
        printQRInTerminal: false,
        syncFullHistory: false,
        markOnlineOnConnect: false,
        qrTimeout: 60_000,
      });

      this.sock = sock;

      sock.ev.on('creds.update', saveCreds);

      sock.ev.on('connection.update', (update) => {
        this.handleConnectionUpdate(update);
      });
    } catch (error) {
      console.error('Failed to start WhatsApp:', error);
      this.scheduleReconnect();
    } finally {
      this.isStarting = false;
    }
  }

  private handleConnectionUpdate(update: Partial<ConnectionState>): void {
    const { connection, lastDisconnect, qr } = update;

    if (qr) {
      console.log('\n========== SCAN QR INI ==========');
      console.log('WhatsApp > Linked Devices > Link a Device');
      qrcode.generate(qr, { small: true });
      console.log('=================================\n');
    }

    if (connection === 'open') {
      console.log('Connected successfully');
      this.isReady = true;
      return;
    }

    if (connection === 'close') {
      this.isReady = false;

      const statusCode = this.getDisconnectStatusCode(lastDisconnect?.error);
      const loggedOut = statusCode === DisconnectReason.loggedOut;

      console.log(
        `Disconnected (code: ${statusCode ?? 'unknown'}). Reconnect: ${!loggedOut}`
      );

      if (loggedOut) {
        console.log('Logged out. Delete the auth folder and restart to get a new QR.');
        this.stopReconnect = true;
        void this.destroySocket();
        return;
      }

      this.scheduleReconnect();
    }
  }

  private scheduleReconnect(): void {
    if (this.stopReconnect || this.reconnectTimer || this.isStarting) {
      return;
    }

    console.log(`Reconnecting in ${RECONNECT_DELAY_MS / 1000} seconds...`);

    this.reconnectTimer = setTimeout(() => {
      this.reconnectTimer = null;
      void this.initialize().catch((error: unknown) => {
        console.error('Reconnect failed:', error);
      });
    }, RECONNECT_DELAY_MS);
  }

  private async destroySocket(): Promise<void> {
    const sock = this.sock;
    this.sock = null;
    this.isReady = false;

    if (!sock) {
      return;
    }

    try {
      sock.ev.removeAllListeners('connection.update');
      sock.ev.removeAllListeners('creds.update');
      await sock.end(undefined);
    } catch {
      // ignore cleanup errors
    }
  }

  private getDisconnectStatusCode(
    error: Boom | Error | undefined
  ): number | undefined {
    if (!error) {
      return undefined;
    }

    if (error instanceof Boom) {
      return error.output.statusCode;
    }

    if ('output' in error) {
      const output = (error as Boom).output;
      if (output && typeof output.statusCode === 'number') {
        return output.statusCode;
      }
    }

    return undefined;
  }

  ensureReady(): void {
    if (!this.sock || !this.isReady) {
      throw new ServiceUnavailableError('WhatsApp bot is not ready');
    }
  }

  async getGroups(): Promise<WhatsAppGroup[]> {
    this.ensureReady();

    const groups = await this.sock!.groupFetchAllParticipating();

    return Object.values(groups).map((group) => ({
      id: group.id,
      subject: group.subject,
    }));
  }

  async sendGroupMessage(groupId: string, message: string): Promise<void> {
    this.ensureReady();

    await this.sock!.sendMessage(groupId, {
      text: message,
    });
  }

  async sendGroupImage(
    groupId: string,
    imageBuffer: Buffer,
    caption?: string
  ): Promise<void> {
    this.ensureReady();

    await this.sock!.sendMessage(groupId, {
      image: imageBuffer,
      caption: caption || '',
    });
  }

  async sendMessage(phone: string, message: string): Promise<void> {
    this.ensureReady();

    const jid = phone.includes('@s.whatsapp.net')
      ? phone
      : `${phone}@s.whatsapp.net`;

    await this.sock!.sendMessage(jid, {
      text: message,
    });
  }

  isBotReady(): boolean {
    return this.isReady && this.sock !== null;
  }
}

export default new WhatsAppBotService();
