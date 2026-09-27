import { Request, Response, NextFunction } from 'express';
import { AppError, ApiResponse } from '../types';

export const errorHandler = (
  err: Error | AppError,
  _req: Request,
  res: Response,
  _next: NextFunction
): void => {
  console.error('Error:', err);

  if (err instanceof AppError) {
    const response: ApiResponse = {
      success: false,
      error: err.message,
      code: err.code,
      details: err.details,
    };
    res.status(err.statusCode).json(response);
    return;
  }

  // Handle entity too large error
  if ('type' in err && err.type === 'entity.too.large') {
    const response: ApiResponse = {
      success: false,
      error: 'Request body too large. Maximum size is 10MB',
      code: 'PAYLOAD_TOO_LARGE',
    };
    res.status(413).json(response);
    return;
  }

  // Handle unknown errors
  const response: ApiResponse = {
    success: false,
    error: 'An unexpected error occurred',
    code: 'INTERNAL_ERROR',
    details: err.message,
  };
  res.status(500).json(response);
};

export const asyncHandler = (fn: Function) => {
  return (req: Request, res: Response, next: NextFunction) => {
    Promise.resolve(fn(req, res, next)).catch(next);
  };
};
