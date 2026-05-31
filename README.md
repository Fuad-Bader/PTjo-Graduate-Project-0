# PTjo (Graduation Project)

PTjo is a static front-end prototype for an offensive security platform. It includes:
- Landing page and industry pages
- Customer dashboard flow (post a security bounty)
- Hacker dashboard and profile pages
- Settings page and demo report seeder

## How to run

This project is **static HTML/JS**. For best results, serve it with a local web server (recommended) so relative paths and scripts work correctly.

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

### Docker

**1. Create a Dockerfile** in the project root:
```dockerfile
FROM nginx:alpine
COPY . /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

**2. Build the Docker image:**
```bash
docker build -t ptjo-app .
```

**3. Run the container:**
```bash
docker run -p 5500:80 ptjo-app
```

Then open `http://localhost:5500/` in your browser.

### Docker Desktop

**1. Ensure Docker Desktop is installed and running**

**2. Create a Dockerfile** in the project root (same as Docker section above)

**3. Build the image:**
```bash
docker build -t ptjo-app .
```

**4. Run the container from Docker Desktop:**
- Open Docker Desktop
- Go to **Images** tab
- Find `ptjo-app` image
- Click the **Run** button
- Set **Host Port** to `5500` and **Container Port** to `80`
- Click **Run**

Or use the command line:
```bash
docker run -p 5500:80 ptjo-app
```

Then open `http://localhost:5500/` in your browser.

**Optional: Stop and remove the container**
```bash
docker stop <container-id>
docker rm <container-id>
```

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
