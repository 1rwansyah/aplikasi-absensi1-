import { Router } from 'express';
import whatsappController from '../controllers/WhatsAppController';
import { verifyBotToken } from '../middleware/auth';
import { asyncHandler } from '../middleware/errorHandler';
import whatsappBotService from '../services/WhatsAppBotService';

const router = Router();

// Health check (no auth required)
router.get('/health', asyncHandler(whatsappController.health.bind(whatsappController)));

// Middleware to ensure bot is ready
const ensureBotReady = (_req: any, res: any, next: any) => {
  if (!whatsappBotService.isBotReady()) {
    res.status(503).json({
      success: false,
      error: 'WhatsApp bot is not ready',
    });
    return;
  }
  next();
};

// Protected routes
router.get(
  '/groups',
  verifyBotToken,
  ensureBotReady,
  asyncHandler(whatsappController.getGroups.bind(whatsappController))
);

router.post(
  '/send-group',
  verifyBotToken,
  ensureBotReady,
  asyncHandler(whatsappController.sendGroupMessage.bind(whatsappController))
);

router.post(
  '/send-group-image',
  verifyBotToken,
  ensureBotReady,
  asyncHandler(whatsappController.sendGroupImage.bind(whatsappController))
);

router.post(
  '/send-message',
  verifyBotToken,
  ensureBotReady,
  asyncHandler(whatsappController.sendMessage.bind(whatsappController))
);

export default router;
