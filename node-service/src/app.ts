import express, { Application } from 'express';
import { errorHandler } from './middleware/errorHandler';
import whatsappRoutes from './routes/whatsapp';
import pdfRoutes from './routes/pdf';

const createApp = (): Application => {
  const app = express();

  // Middleware
  app.use(express.json({ limit: '10mb' }));

  // Request size limit error handler
  app.use((err: any, _req: express.Request, res: express.Response, next: express.NextFunction) => {
    if (err.type === 'entity.too.large') {
      res.status(413).json({
        error: 'Request body too large. Maximum size is 10MB',
        code: 'PAYLOAD_TOO_LARGE'
      });
      return;
    }
    next(err);
  });

  // Routes
  app.use('/wa', whatsappRoutes);
  app.use('/pdf', pdfRoutes);

  // Error handling middleware (must be last)
  app.use(errorHandler);

  return app;
};

export default createApp;
