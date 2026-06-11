# Campus Events and Navigation System

A static GitHub Pages website that allows students, staff, and visitors to
discover and explore campus events.

---

## 📋 Project Info

| Field         | Details                              |
|---------------|--------------------------------------|
| Group         | Cloud Lab Group                      |
| Course        | Cloud Computing & Web Technologies   |
| Academic Year | 2025 / 2026                          |
| Platform      | GitHub Pages (Static)                |
| Tech Stack    | HTML5 · CSS3 · Vanilla JavaScript    |

---

## 📁 Project Structure

```
cloud-lab-group-project/
│
├── index.html          # Home page — event listing & search
├── about.html          # About page — Detailed information about the system
├── contact.html        # Contact page — contact form
├──  team.html          #Team page - Details about the Team
|
├── images              # Files containing the images of the event's venues 
│
├── css/
│   └── style.css       # All styles for the site
│
├── js/
│   └── app.js         # All JavaScript (event fetching, search, filters)
│
└── README.md          # Project documentation (this file)
```

---

## 🚀 Getting Started

### View Locally

No build tools or server required. Simply open `index.html` in a browser,
or use the [Live Server](https://marketplace.visualstudio.com/items?itemName=ritwickdey.LiveServer)
VS Code extension for hot-reload during development.

> **Note:** Fetching `events.json` via `fetch()` requires a local server
> (Live Server or similar) due to browser CORS restrictions on `file://` URLs.

### Deploy to GitHub Pages

1. Push the repository to GitHub.
2. Go to **Settings → Pages**.
3. Set **Source** to the `main` branch, root `/` directory.
4. GitHub will publish the site at [](https://chrisolega.github.io/cloud-lab-group-project/).

---
---

## 👥 Team Members

<!-- TODO: Fill in team member names and roles -->

| Name                                     | Role / Responsibility                                                                                                                                  |
|------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------|
| Christabel Addomaa Danso(2425402244)     | Group Leader & Frontend Developer (team.html) / Led the group, developed the Team page, coordinated tasks, and enforced timelines.                     |
| Christian Gift Kwesi Tetteh(2425402198)  | GitHub Manager & Frontend Developer (index.html) / Created and managed the GitHub repo, set up GitHub Pages, developed homepage layout and navigation. |
| Yvette Ayitey(2425403936)                |Frontend Developer (contact.html)/ Designed the Contact page with communication details and a contact form.                                             |
| Daniel Edem Edzeani(2425402513)          | CSS, JavaScript & JSON Developer/ Styled the site with CSS, added interactivity with JavaScript, managed JSON data, improved UX and consistency.       |
| Julius Mortey(2425402382)                | Frontend Developer (about.html) / Developed the About page, ensured readability, structure, and design consistency.                                    |

---

## 📄 License

This project is submitted as coursework for academic assessment purposes.
