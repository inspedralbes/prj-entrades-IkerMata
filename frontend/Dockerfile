FROM node:20-alpine

WORKDIR /usr/src/app

COPY package*.json ./

# Disable postinstall scripts for now to avoid 'nuxt prepare' issues during build
RUN npm install 

COPY . .

EXPOSE 3000

# Dev mode
CMD ["npm", "run", "dev", "--", "--host"]
