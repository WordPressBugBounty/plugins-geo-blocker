=== Geo Blocker – Control Site Access by Region and IP ===
Contributors: medshi8  
Tags: geo blocking, IP blocker, country restriction, access control, geolocation, WordPress security  
Requires at least: 5.0  
Tested up to: 6.8  
Stable tag: 1.0.0  
Requires PHP: 7.2  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

🔐 Block or allow visitors by country. Track access attempts. View analytics. Stay in control — effortlessly.

== 🧠 Description ==

**Geo Blocker** gives you full control over who can access your WordPress site — based on visitor country and IP. Whether you're protecting content, reducing attack surface, or managing regional access, this plugin does it with precision and clarity.

🎯 Designed for performance, security, and ease of use.  
📊 Built-in analytics and access logs.  
🧭 Never get locked out — admin-safe bypass included.

== 🚀 Features ==

### ✅ Access Control That Makes Sense
- **Block Selected Countries** – deny access to specific regions  
- **Allow Selected Countries** – restrict site only to approved countries  

### 🧩 Smart Blocking Actions
- 📜 Show custom message  
- 🔁 Redirect to a URL  
- 🚫 Send HTTP 403 Forbidden response  

### 🌐 Visual Country Selector
- Flag icons & search bar for quick targeting  
- Filter by continent (Africa, Asia, Europe, etc.)  
- One-click select/deselect all  

### 📈 Analytics Dashboard
- Summary cards: total visits, blocks, IPs  
- Hourly charts for real-time insights  
- Filter by date range & data type (accesses, unique IPs, etc.)  

### 📋 Detailed Logs
- See IP, country, URL, status, user agent  
- Filters out common junk (favicon, robots.txt)  
- Admin visits are auto-ignored to reduce noise  

### 🛠️ Admin-Proof Bypass URL
- Special URL with bypass parameter to access login anytime  
- Prevents accidental lockouts  

### 🔄 Data Export & Log Management
- Export logs in CSV or JSON  
- Clear logs with a single click  
- Sort & search logs in the UI  

== 🌐 External Services ==

This plugin uses a third-party API to determine the visitor's country based on their IP address.

- **Service used:** [IPWho.is](https://ipwho.is)  
- **Purpose:** To perform IP geolocation and detect the country of each visitor, allowing the plugin to block or allow access accordingly.  
- **Data sent:** The visitor’s IP address is sent to the IPWho.is API on page load when geo-blocking is active.  
- **Terms of Service:** [https://ipwhois.io/terms](https://ipwhois.io/terms)  
- **Privacy Policy:** [https://ipwhois.io/privacy](https://ipwhois.io/privacy)  

== 🖥️ Screenshots ==

1. **📊 Dashboard Overview** – See country blocks, allowed hits & total attempts   
2. **🔧 Blocking Rules** – Choose block mode, action type, and targets. Enable or disable countries visually
3. **📉 Analytics Graphs** – View access by time, state, and IP 5. **📑 Logs Table** – Deep insights with full logs of visitor attempts. Export CSV/JSON logs with one click  


== 📦 Installation ==

1. Upload the plugin folder to `/wp-content/plugins/geo-blocker`  
2. Activate via **Plugins → Installed Plugins**  
3. Go to **Settings → Geo Blocker**  
4. Enable Geo Blocking using the toggle  
5. Choose between **block** or **allow** mode  
6. Select countries using the visual interface  
7. Pick your blocking action (message, redirect, or 403)  
8. Save settings — done!  

== ❓ Frequently Asked Questions ==

### How does Geo Blocker detect country?
It uses the reliable **IpWhoIs API** to fetch country data based on the visitor’s IP.

### Will it slow down my site?
Nope. It's optimized with **transient caching** and smart triggers — no unnecessary lookups.

### Can I lock myself out?
No. There's a **login bypass URL** generated for administrators — shown right on the dashboard.

### Can I block specific pages?
Not yet — current version works site-wide. Per-page rules may come in a future update.

### Can I export visitor logs?
Yes. Logs can be exported in **CSV or JSON** format directly from the Logs tab.

### Does it work with caching plugins?
Yes, but you may need to **exclude the plugin's logic** from caching. Dynamic geo checks should not be cached.

== 🗂️ Changelog ==

= 1.0.0 =  
- 🎉 Initial release with all core features  

== 🛡️ Additional Notes ==

### Emergency Bypass
Every admin gets a custom bypass link to avoid accidental lockouts. It’s always visible in the dashboard.

### Blocking Actions
Choose the experience blocked users receive:
- Custom message
- Redirect to another URL
- Send 403 Forbidden header

### Logs & Privacy
Logs are stored locally in your WordPress database. The plugin sends only the visitor's IP to IPWho.is — no personally identifiable information is shared or stored externally.

---

== 💡 Enjoying Geo Blocker? Try Our Other Free Plugins ==

Looking for even more control and peace of mind? Check out our other tools:

🔕 **[Notification Blocker](https://wordpress.org/plugins/notification-blocker/)** – Hide annoying plugin notices from your dashboard without hacking core files.

🛡️ **[Fortress Login Pro](https://wordpress.org/plugins/fortress-login-pro/)** – Obscure your login page, add brute-force protection, and block unauthorized access attempts with ease.

If you like Geo Blocker, you’ll probably find these just as helpful. Try them out!

---