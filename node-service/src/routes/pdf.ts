import { Router } from 'express';
import pdfController from '../controllers/PdfController';
import { authenticatePdfToken } from '../middleware/auth';
import { asyncHandler } from '../middleware/errorHandler';

const router = Router();

// Health check (no auth required)
router.get(
  '/health',
  asyncHandler(pdfController.health.bind(pdfController))
);

// Generate PDF (auth required)
router.post(
  '/generate-pdf',
  authenticatePdfToken,
  asyncHandler(pdfController.generatePdf.bind(pdfController))
);

export default router;
