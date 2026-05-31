# PTjo (Graduation Project)

PTjo is a static front-end prototype for an offensive security platform. It includes:
- Landing page and industry pages
- Customer dashboard flow (post a security bounty)
- Hacker dashboard and profile pages
- Settings page and demo report seeder

## How to run

This project is **static HTML/JS**. For best results, serve it with a local web server (recommended) so relative paths and scripts work correctly.

- **VS Code**: install “Live Server” → right-click `index.html` → **Open with Live Server**
- **Python** (if installed):

```bash
python -m http.server 5500
```

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
- The **Industries** dropdown includes an “All Industries” link to `Industries/index.html`

