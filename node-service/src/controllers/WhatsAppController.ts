import { Request, Response } from 'express';
import axios from 'axios';
import whatsappBotService from '../services/WhatsAppBotService';
import { ValidationError, ApiResponse } from '../types';

class WhatsAppController {
  async health(_req: Request, res: Response): Promise<void> {
    res.json({
      success: true,
      message: 'WA bot is running',
    });
  }

  async getGroups(_req: Request, res: Response): Promise<void> {
    const groups = await whatsappBotService.getGroups();
    res.json(groups);
  }

  async sendGroupMessage(req: Request, res: Response): Promise<void> {
    const { groupId, message } = req.body;

    if (!groupId || !message) {
      throw new ValidationError('groupId and message are required');
    }

    await whatsappBotService.sendGroupMessage(groupId, message);

    const response: ApiResponse = {
      success: true,
    };
    res.json(response);
  }

  async sendGroupImage(req: Request, res: Response): Promise<void> {
    const { groupId, imageUrl, caption } = req.body;

    if (!groupId || !imageUrl) {
      throw new ValidationError('groupId and imageUrl are required');
    }

    const response = await axios.get(imageUrl, {
      responseType: 'arraybuffer',
    });

    await whatsappBotService.sendGroupImage(
      groupId,
      Buffer.from(response.data),
      caption
    );

    const apiResponse: ApiResponse = {
      success: true,
    };
    res.json(apiResponse);
  }
  async sendMessage(req: Request, res: Response): Promise<void> {
    const { phone, message } = req.body;

    if (!phone || !message) {
      throw new ValidationError('phone and message are required');
    }

    await whatsappBotService.sendMessage(phone, message);

    const response: ApiResponse = {
      success: true,
    };
    res.json(response);
  }
}

export default new WhatsAppController();
