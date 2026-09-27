import { Request, Response, NextFunction } from 'express';
import config from '../config';
import { UnauthorizedError } from '../types';

export const verifyBotToken = (
  req: Request,
  _res: Response,
  next: NextFunction
): void => {
  const authHeader = req.headers.authorization;

  if (!config.botToken) {
    throw new UnauthorizedError('BOT_TOKEN is not configured');
  }

  if (authHeader !== `Bearer ${config.botToken}`) {
    throw new UnauthorizedError('Invalid bot token');
  }

  next();
};

export const authenticatePdfToken = (
  req: Request,
  _res: Response,
  next: NextFunction
): void => {
  // Skip authentication if no token is configured
  if (!config.authToken) {
    return next();
  }

  const authHeader = req.headers['authorization'];
  const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

  if (!token) {
    throw new UnauthorizedError('Authorization token is required');
  }

  if (token !== config.authToken) {
    throw new UnauthorizedError('Invalid authorization token');
  }

  next();
};
