FROM node:20-alpine

WORKDIR /usr/src/app

COPY package*.json ./

# Disable postinstall scripts for now to avoid 'nuxt prepare' issues during build
RUN npm install 

COPY . .

EXPOSE 3000

# Dev: sincronitza node_modules del volum amb package.json muntat des del host
CMD ["sh", "-c", "npm install && npm run dev -- --host"]
