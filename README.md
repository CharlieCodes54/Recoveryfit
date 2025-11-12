# MemberPress Corporate Membership Dashboard

A clean, responsive reporting dashboard that embeds Google Sheets data for MemberPress corporate membership tracking.

## 🚀 Live Demo

Deploy this to Vercel, Netlify, or any static hosting service. The dashboard will automatically load your Google Sheets data.

## 📋 Setup Instructions

### Step 1: Publish Your Google Sheet

For the dashboard to display your data, you need to publish the Google Sheet to the web:

1. Open your Google Sheet: [https://docs.google.com/spreadsheets/d/1PeCWYZKM9k_n63jGRYF6vTrizBx8v5pehiAFatd5ifU/edit?gid=369191302#gid=369191302](https://docs.google.com/spreadsheets/d/1PeCWYZKM9k_n63jGRYF6vTrizBx8v5pehiAFatd5ifU/edit?gid=369191302#gid=369191302)

2. Click **File** → **Share** → **Publish to web**

3. In the dialog:
   - Choose which sheet/tab to publish (or select "Entire Document")
   - Choose format: **Web page** or **Link**
   - Click **Publish**
   - Confirm the prompt

4. Alternatively, you can make the sheet publicly viewable:
   - Click **Share** button (top right)
   - Click **Change to anyone with the link**
   - Set permission to **Viewer**
   - Click **Done**

### Step 2: Deploy to Vercel

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new)

1. Push this repository to GitHub
2. Import the project in Vercel
3. Deploy (no build configuration needed)
4. Access your dashboard at `your-project.vercel.app`

### Step 3: Embed in GHL (GoHighLevel) Pages

To embed this dashboard in GoHighLevel pages:

1. Deploy the dashboard to Vercel (see Step 2)
2. In GHL, add a **Custom HTML** element to your page
3. Use this embed code:

```html
<iframe
  src="https://your-project.vercel.app"
  width="100%"
  height="800"
  frameborder="0"
  style="border: none; border-radius: 8px;"
  allowfullscreen
></iframe>
```

Replace `your-project.vercel.app` with your actual Vercel URL.

## 🎨 Features

- **Responsive Design** - Works on desktop, tablet, and mobile
- **Dark Theme UI** - Modern, easy-on-the-eyes interface
- **Full-Screen Embed** - Maximizes data visibility
- **Error Handling** - Clear instructions if sheet isn't accessible
- **Multiple Embed Attempts** - Tries different URL formats automatically
- **Direct Sheet Access** - Quick link to open in Google Sheets

## 🔧 Customization

### Update Sheet ID

To use a different Google Sheet, edit `index.html` and update these lines:

```javascript
const SHEET_ID = 'YOUR_SHEET_ID_HERE';
const GID = 'YOUR_GID_HERE';
```

**How to find these values:**

From a Google Sheets URL like:
```
https://docs.google.com/spreadsheets/d/1PeCWYZKM9k_n63jGRYF6vTrizBx8v5pehiAFatd5ifU/edit?gid=369191302
```

- **SHEET_ID**: `1PeCWYZKM9k_n63jGRYF6vTrizBx8v5pehiAFatd5ifU`
- **GID**: `369191302`

### Customize Styling

All colors and styling are defined in CSS variables at the top of `index.html`:

```css
:root {
  --bg-main: #020817;       /* Main background */
  --bg-header: #050816;     /* Header background */
  --accent: #38bdf8;        /* Accent color */
  --text-main: #f1f5f9;     /* Primary text */
  /* ... more variables */
}
```

## 📁 Project Structure

```
.
├── index.html              # Main dashboard file (standalone)
├── RFA Member Report Page  # Legacy report page
└── README.md              # This file
```

## 🐛 Troubleshooting

### Issue: "Document has not been published"

**Solution:** Follow Step 1 above to publish the Google Sheet to the web.

### Issue: 404 on Vercel

**Solution:** Make sure the file is named `index.html` (not `memberpress-dashboard.html`).

### Issue: Sheet not loading in iframe

**Possible causes:**
1. Sheet not published (see Step 1)
2. Sheet sharing settings too restrictive
3. Browser blocking iframes (check console for errors)

**Fix:**
- Ensure sheet is published or set to "Anyone with link can view"
- Try opening the dashboard in a different browser
- Check browser console (F12) for error messages

### Issue: Embed not working in GHL

**Solution:**
1. Make sure you deployed to Vercel first (can't embed local files)
2. Use the full Vercel URL in the iframe src
3. Check that GHL allows iframe embeds (some page builders restrict this)

## 📊 Data Source

This dashboard is configured to display MemberPress corporate membership data from Google Sheets. The sheet should include:

- Member/company information
- Subscription status
- Usage metrics
- Corporate account details

## 🔒 Security Notes

- The Google Sheet must be publicly accessible for the embed to work
- Don't include sensitive data (passwords, API keys, etc.) in published sheets
- Consider using Google Sheets data filtering if you need to hide certain columns

## 📝 License

This project is open source and available for use in your MemberPress reporting workflows.

## 🤝 Contributing

Feel free to submit issues or pull requests for improvements!

---

**Need help?** Check the warning banner in the dashboard for step-by-step setup instructions.
