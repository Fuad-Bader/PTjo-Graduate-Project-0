# PTjo (Graduation Project)

PTjo is a static front-end prototype for an offensive security platform. It includes:
- Landing page and industry pages
- Customer dashboard flow (post a security bounty)
- Hacker dashboard and profile pages
- Settings page and demo report seeder

## How to run

This project is **static HTML/JS**. For best results, serve it with a local web server (recommended) so relative paths and scripts work correctly.

### Docker Compose

**Prerequisites:**
1. Download Docker Desktop from [docker.com](https://www.docker.com/products/docker-desktop)
2. Install Docker Desktop for your operating system (Windows, Mac, or Linux)
3. Launch Docker Desktop and wait for it to fully start (you'll see the Docker icon in your system tray/menu bar)
4. Verify Docker is running by opening a terminal and running:
   ```bash
   docker --version
   ```

**Run the application:**
```bash
docker compose up --build -d
```

**Access the application:**
Open `http://localhost:5500/` in your browser and start from `index.html`.

**Stop the container:**
```bash
docker compose down
```

### Windows

**Option 1: Python**
```bash
python -m http.server 5500
```

**Option 2: Node.js (http-server)**
```bash
npm install -g http-server
http-server . -p 5500
```

**Option 3: VS Code Live Server**
- Install "Live Server" extension
- Right-click `index.html` → **Open with Live Server**

Then open `http://localhost:5500/` and start from `index.html`.

### Linux

**Option 1: Python**
```bash
python3 -m http.server 5500
```

**Option 2: Node.js (http-server)**
```bash
npm install -g http-server
http-server . -p 5500
```

**Option 3: PHP (if installed)**
```bash
php -S localhost:5500
```

**Option 4: Using Apache or Nginx**
Configure your web server to serve files from the project directory.

Then open `http://localhost:5500/` and start from `index.html`.

## Main pages

- **Home**: `index.html`
- **Industries**: `Industries/index.html`
  - Finance: `Industries/finance.html`
  - Healthcare: `Industries/healthcare.html`
  - Retail: `Industries/retail.html`
  - Technology: `Industries/technology.html`
- **Customer Dashboard**: `Customer_Dashboard/Customer_Dashboard.html`
- **Hacker Dashboard**: `Hacker_Dashboard/HackerDashboard.html`
- **Customer Profile**: `My_ProfileCustomer/MyProfileC.html`
- **Hacker Profile**: `My_ProfileHacker/MyProfile.html`
- **Settings**: `Settings/Settings.html`
- **Demo report seeder**: `assets/seed-example-report.html`

## Navigation/linking notes

- The **Products** dropdown generally routes to `Customer_Dashboard/Customer_Dashboard.html?service=...`
- The **Industries** dropdown includes an "All Industries" link to `Industries/index.html`
