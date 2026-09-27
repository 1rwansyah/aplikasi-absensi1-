import { Request, Response } from 'express';
import pdfService from '../services/PdfService';
import { ValidationError, HealthResponse } from '../types';

class PdfController {
  async health(_req: Request, res: Response): Promise<void> {
    const response: HealthResponse = {
      status: 'ok',
      service: 'pdf-service',
      version: '1.0.0',
      timestamp: new Date().toISOString(),
    };
    res.json(response);
  }

  async generatePdf(req: Request, res: Response): Promise<void> {
    const { html, filename, paper = 'A4', orientation = 'portrait' } = req.body;

    // Validate input
    if (!html) {
      throw new ValidationError('HTML content is required', 'INVALID_HTML');
    }

    if (typeof html !== 'string' || html.trim().length === 0) {
      throw new ValidationError(
        'HTML content must be a non-empty string',
        'INVALID_HTML'
      );
    }

    // Generate PDF
    const pdfBuffer = await pdfService.generatePdf(html, {
      format: paper,
      orientation,
      printBackground: true,
      margin: {
        top: '5mm',
        right: '5mm',
        bottom: '5mm',
        left: '5mm',
      },
    });

    // Set response headers
    res.setHeader('Content-Type', 'application/pdf');

    if (filename) {
      res.setHeader('Content-Disposition', `attachment; filename="${filename}.pdf"`);
    }

    // Send PDF binary
    res.send(pdfBuffer);
  }
}

export default new PdfController();
