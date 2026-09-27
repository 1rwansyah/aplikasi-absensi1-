import puppeteer from 'puppeteer';
import { PdfOptions } from '../types';
import { ServiceUnavailableError, InternalError } from '../types';

class PdfService {
  private browser: any | null = null;

  async getBrowser(): Promise<any> {
    // Check if browser exists and is still connected
    if (this.browser) {
      try {
        // Try to get browser version to verify connection
        await this.browser.version();
      } catch (error) {
        // Browser is disconnected, reset it
        console.log('Browser disconnected, creating new instance');
        this.browser = null;
      }
    }

    if (!this.browser) {
      try {
        console.log('Launching new browser instance');
        this.browser = await puppeteer.launch({
          headless: true,
          args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage',
            '--disable-accelerated-2d-canvas',
            '--no-first-run',
            '--no-zygote',
            '--disable-gpu',
          ],
        });

        // Register disconnected handler
        this.browser.on('disconnected', () => {
          console.log('Browser disconnected event received');
          this.browser = null;
        });

        console.log('Browser launched successfully');
      } catch (error) {
        console.error('Browser launch failed:', error);
        this.browser = null;
        throw new ServiceUnavailableError(
          'Failed to launch browser. Service may be unavailable.',
          error instanceof Error ? error.message : String(error)
        );
      }
    }
    return this.browser;
  }

  async generatePdf(html: string, options: PdfOptions = {}): Promise<Buffer> {
    console.log('PDF request started');
    let browserInstance: any | null;
    let page: any | null = null;
    let retryCount = 0;

    try {
      browserInstance = await this.getBrowser();
    } catch (error) {
      console.error('Browser launch error:', error);
      throw new ServiceUnavailableError(
        'Failed to launch browser. Service may be unavailable.',
        error instanceof Error ? error.message : String(error)
      );
    }

    // Retry logic for page creation
    while (retryCount <= 1) {
      try {
        page = await browserInstance.newPage();
        break; // Success, exit retry loop
      } catch (error) {
        console.error('Failed to create page:', error);
        
        if (retryCount === 0) {
          // First failure: try to recreate browser and retry
          console.log('Browser may be disconnected, recreating browser and retrying');
          this.browser = null;
          try {
            browserInstance = await this.getBrowser();
            retryCount++;
            continue; // Retry once
          } catch (retryError) {
            console.error('Browser recreation failed:', retryError);
            throw new ServiceUnavailableError(
              'Failed to recreate browser after page creation failure.',
              error instanceof Error ? error.message : String(error)
            );
          }
        } else {
          // Second failure: give up
          console.error('Page creation failed after retry');
          throw new InternalError(
            'Failed to create page after browser recreation.',
            error instanceof Error ? error.message : String(error)
          );
        }
      }
    }

    try {
      // Set content and wait for it to load
      try {
        await page.setContent(html, {
          waitUntil: 'networkidle0',
          timeout: 30000,
        });
      } catch (error) {
        if (error instanceof Error && error.name === 'TimeoutError') {
          throw new ServiceUnavailableError(
            'HTML content loading timeout. The page took too long to load.',
            error.message
          );
        }
        throw error;
      }

      // Generate PDF
      const pdfBuffer = await page.pdf({
        format: options.format || 'A4',
        orientation: options.orientation || 'portrait',
        printBackground: options.printBackground !== false,
        margin: {
          top: '0mm',
          right: '0mm',
          bottom: '0mm',
          left: '0mm',
        },
      });

      console.log('PDF generated successfully');
      return pdfBuffer;
    } catch (error) {
      console.error('PDF generation error:', error);
      throw new InternalError(
        'Failed to generate PDF from HTML content',
        error instanceof Error ? error.message : String(error)
      );
    } finally {
      if (page) {
        await page.close();
      }
    }
  }

  async close(): Promise<void> {
    if (this.browser) {
      await this.browser.close();
      this.browser = null;
    }
  }
}

export default new PdfService();
