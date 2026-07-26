# 🚀 Deployment Guide for Render

This guide provides step-by-step instructions to deploy the **AI Meeting Minutes Summarizer** application to [Render](https://render.com/).

---

## 📌 Deployment Overview

Because PHP applications with MySQL dependencies run smoothly inside Docker containers, we deploy this application on Render as a **Docker Web Service**. 

The app features a **Dual Database Architecture**:
1. **With MySQL (Full Mode)**: Connect to any remote MySQL database (e.g., Aiven, Railway, PlanetScale, TiDB, or AWS RDS).
2. **Without MySQL (Demo Mode)**: If no database environment variables are set or MySQL is unreachable, the application automatically operates in session-based demo mode without failing.

---

## 🛠️ Step 1: Push Project to GitHub

1. Initialize git (if not already initialized) and commit your code:
   ```bash
   git init
   git add .
   git commit -m "Prepare AI Meeting Summarizer for Render deployment"
   ```

2. Create a new repository on [GitHub](https://github.com/new).

3. Link your local repo and push:
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/ai-meeting-summarizer.git
   git branch -M main
   git push -u origin main
   ```

---

## ☁️ Step 2: Deploy on Render

### Option A: Standard Deploy (Web Service)

1. Log in to [Render Dashboard](https://dashboard.render.com/).
2. Click **New +** > **Web Service**.
3. Connect your **GitHub Repository** (`ai-meeting-summarizer`).
4. Configure the Web Service settings:
   - **Name**: `ai-meeting-summarizer` (or your preferred name)
   - **Language / Environment**: `Docker`
   - **Branch**: `main`
   - **Region**: Select closest to your users
   - **Plan**: `Free`
5. Click **Create Web Service**. Render will automatically detect the `Dockerfile` and start building the container.

---

### Option B: Render Blueprint Deploy (`render.yaml`)

1. Log in to [Render Dashboard](https://dashboard.render.com/).
2. Click **New +** > **Blueprint**.
3. Select your GitHub repository.
4. Render will automatically read `render.yaml` and configure the web service.
5. Click **Apply**.

---

## 🔑 Step 3: Configure Environment Variables

In your Render Dashboard, navigate to your Web Service > **Environment**:

| Key | Value / Example | Description |
|---|---|---|
| `GEMINI_API_KEY` | `AIzaSy...` | *(Optional)* Your Google Gemini API Key for AI summarization. |
| `DB_HOST` | `mysql-xxxx.aivencloud.com` | *(Optional)* Remote MySQL Database host. |
| `DB_PORT` | `3306` | *(Optional)* MySQL Port (default 3306). |
| `DB_NAME` | `ai_meeting_db` | *(Optional)* MySQL Database name. |
| `DB_USER` | `root` / `avnadmin` | *(Optional)* MySQL Username. |
| `DB_PASS` | `your_db_password` | *(Optional)* MySQL Password. |

> 💡 **Alternatively**, if your database provider supplies a single connection URL, set `DATABASE_URL` or `MYSQL_URL` (e.g. `mysql://user:pass@host:3306/dbname`).

---

## 🗄️ Step 4: Remote Database Setup (Optional)

If you wish to use persistent MySQL storage:
1. Create a free MySQL database on [Aiven for MySQL](https://aiven.io/), [Railway](https://railway.app/), or [TiDB Serverless](https://tidbcloud.com/).
2. Open your database client (DBeaver, phpMyAdmin, or MySQL CLI).
3. Execute the SQL script located in `database/schema.sql` to create the required tables (`users`, `meetings`, `action_items`, `key_decisions`).
4. Copy the connection details into Render's **Environment Variables**.

---

## ✅ Step 5: Verify Deployment

Once Render finishes building:
1. Open your Render Web Service URL (e.g., `https://ai-meeting-summarizer.onrender.com`).
2. Test creating a new meeting summary using voice recording or text transcript.
3. Test dual mode (Gemini API key or offline smart NLP summarizer fallback).

---

## ❓ Frequently Asked Questions & Troubleshooting

### 1. Does the Web Speech API work on Render?
Yes! The Web Speech API runs directly in the client's browser. Render serves the app via HTTPS (`https://`), which is required by browsers for Web Speech API microphone access.

### 2. What if I don't set up MySQL?
The application gracefully falls back to **Smart Demo Mode**. Users can test and present the full UI, voice recording, offline NLP summarizer, and exports without requiring a running database server.
