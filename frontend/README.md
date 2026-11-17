# CleanCommerce Frontend SPA (Vue 3.5 + TypeScript + Pinia + Axios + SCSS)

Repository Frontend độc lập 100%, đóng gói sẵn `.env.example`, `Dockerfile` và `docker-compose.yml` riêng biệt.

## 1. Chạy bằng Docker
```bash
cp -n .env.example .env
docker compose up -d --build
```
- Mở trình duyệt tại: `http://localhost:5173`

## 2. Chạy Local
```bash
cp -n .env.example .env
npm install
npm run dev
```

## 3. Build & Kiểm tra TypeScript
```bash
npm run build
```
