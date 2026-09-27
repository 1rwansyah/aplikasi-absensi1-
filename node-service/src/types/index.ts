import { Request, Response, NextFunction } from 'express';

// ===============================
// EXPRESS TYPES
// ===============================

export interface AuthenticatedRequest extends Request {
  user?: {
    id: string;
    role: string;
  };
}

// ===============================
// WHATSAPP TYPES
// ===============================

export interface WhatsAppGroup {
  id: string;
  subject: string;
}

export interface SendMessageRequest {
  groupId: string;
  message: string;
}

export interface SendImageRequest {
  groupId: string;
  imageUrl: string;
  caption?: string;
}

export interface ConnectionUpdate {
  connection?: 'open' | 'close';
  qr?: string;
  lastDisconnect?: {
    error?: {
      output?: {
        statusCode?: number;
      };
    };
  };
}

// ===============================
// PDF TYPES
// ===============================

export interface GeneratePdfRequest {
  html: string;
  filename?: string;
  paper?: 'A4' | 'Letter' | 'Legal';
  orientation?: 'portrait' | 'landscape';
}

export interface PdfOptions {
  format?: string;
  orientation?: string;
  printBackground?: boolean;
  margin?: {
    top?: string;
    right?: string;
    bottom?: string;
    left?: string;
  };
}

// ===============================
// RESPONSE TYPES
// ===============================

export interface ApiResponse<T = any> {
  success?: boolean;
  error?: string;
  code?: string;
  details?: string;
  data?: T;
}

export interface HealthResponse {
  status: string;
  service: string;
  version: string;
  timestamp: string;
}

// ===============================
// ERROR TYPES
// ===============================

export class AppError extends Error {
  constructor(
    public statusCode: number,
    public code: string,
    message: string,
    public details?: string
  ) {
    super(message);
    this.name = 'AppError';
  }
}

export class UnauthorizedError extends AppError {
  constructor(message = 'Unauthorized', details?: string) {
    super(401, 'UNAUTHORIZED', message, details);
    this.name = 'UnauthorizedError';
  }
}

export class ValidationError extends AppError {
  constructor(message = 'Validation failed', details?: string) {
    super(422, 'VALIDATION_ERROR', message, details);
    this.name = 'ValidationError';
  }
}

export class NotFoundError extends AppError {
  constructor(message = 'Resource not found', details?: string) {
    super(404, 'NOT_FOUND', message, details);
    this.name = 'NotFoundError';
  }
}

export class ServiceUnavailableError extends AppError {
  constructor(message = 'Service unavailable', details?: string) {
    super(503, 'SERVICE_UNAVAILABLE', message, details);
    this.name = 'ServiceUnavailableError';
  }
}

export class InternalError extends AppError {
  constructor(message = 'Internal server error', details?: string) {
    super(500, 'INTERNAL_ERROR', message, details);
    this.name = 'InternalError';
  }
}

// ===============================
// MIDDLEWARE TYPES
// ===============================

export type AsyncHandler = (
  req: Request,
  res: Response,
  next: NextFunction
) => Promise<void>;
