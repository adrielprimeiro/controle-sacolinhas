FROM node:20-alpine

WORKDIR /var/www

# Install dependencies (if any specific to Vite build)
# COPY package.json package-lock.json ./
# RUN npm install

# Copy application code (handled by docker-compose volume mount)

# Expose Vite's default port
EXPOSE 5173

# Default command (can be overridden by docker-compose)
CMD ["npm", "run", "dev"]