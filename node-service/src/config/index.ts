import dotenv from 'dotenv';

dotenv.config();

interface Config {
  port: number;
  botToken: string | undefined;
  authToken: string | undefined;
  nodeEnv: string;
}

const config: Config = {
  port: parseInt(process.env.PORT || '3000', 10),
  botToken: process.env.BOT_TOKEN,
  authToken: process.env.AUTH_TOKEN,
  nodeEnv: process.env.NODE_ENV || 'development',
};

export default config;
