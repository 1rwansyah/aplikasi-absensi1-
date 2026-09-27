import createApp from './app';
import config from './config';
import whatsappBotService from './services/WhatsAppBotService';
import pdfService from './services/PdfService';

const app = createApp();

// Start WhatsApp bot
void whatsappBotService.initialize().catch((error: unknown) => {
  console.error('WhatsApp bot failed to initialize:', error);
});

// Graceful shutdown handlers
const gracefulShutdown = async (signal: string): Promise<void> => {
  console.log(`Received ${signal}. Shutting down gracefully...`);
  
  try {
    await pdfService.close();
    console.log('PDF service closed.');
  } catch (error) {
    console.error('Error closing PDF service:', error);
  }
  
  process.exit(0);
};

process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));

// Start server
app.listen(config.port, '127.0.0.1', () => {
  console.log(`Unified Service running on http://127.0.0.1:${config.port}`);
  console.log(`WA Bot endpoints:`);
  console.log(`  - Health: http://127.0.0.1:${config.port}/wa/health`);
  console.log(`  - Groups: http://127.0.0.1:${config.port}/wa/groups`);
  console.log(`  - Send Group: http://127.0.0.1:${config.port}/wa/send-group`);
  console.log(`  - Send Group Image: http://127.0.0.1:${config.port}/wa/send-group-image`);
  console.log(`PDF Service endpoints:`);
  console.log(`  - Health: http://127.0.0.1:${config.port}/pdf/health`);
  console.log(`  - Generate PDF: http://127.0.0.1:${config.port}/pdf/generate-pdf`);
});
