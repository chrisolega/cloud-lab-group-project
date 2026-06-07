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
├── about.html          # About page — team & project info
├── contact.html        # Contact page — contact form
│
├── css/
│   └── style.css       # All styles for the site
│
├── js/
│   └── script.js       # All JavaScript (event fetching, search, filters)
│
├── data/
│   ├── events.json     # Campus event data (JSON array)
│   └── venues.json     # Campus venue data (JSON array)
│
├── images/             # Static image assets
│
└── README.md           # Project documentation (this file)
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
4. GitHub will publish the site at `https://<username>.github.io/<repo-name>/`.

---

## 📝 Data Format

### events.json

Each event object should follow this schema:

```json
{
  "id": "unique-event-id",
  "title": "Event Title",
  "date": "YYYY-MM-DD",
  "time": "HH:MM",
  "location": "Venue Name",
  "category": "academic | social | sports | workshop",
  "description": "Short description of the event.",
  "image": "images/event-image.jpg"
}
```

### venues.json

Each venue object should follow this schema:

```json
{
  "id": "unique-venue-id",
  "name": "Venue Name",
  "capacity": 200,
  "building": "Building Name",
  "mapLink": "https://maps.example.com/..."
}
```

---

## 👥 Team Members

<!-- TODO: Fill in team member names and roles -->

| Name           | Role / Responsibility |
|----------------|-----------------------|
| Team Member 1  | Role / Responsibility |
| Team Member 2  | Role / Responsibility |
| Team Member 3  | Role / Responsibility |
| Team Member 4  | Role / Responsibility |

---

## 📄 License

This project is submitted as coursework for academic assessment purposes.
