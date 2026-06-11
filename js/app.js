/**
 * CampusNav — app.js
 * Handles: mobile nav · current year · event fetching & rendering ·
 *          search · filter chips · sort · reminders · interested list
 */

"use strict";

/* ─────────────────────────────────────────────────────────────────
   UTILITIES
───────────────────────────────────────────────────────────────── */

/** Safely query the DOM — returns null instead of throwing */
const $ = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

/** Show / hide elements by toggling the CSS "hidden" class */
const show = el => el && el.classList.remove("hidden");
const hide = el => el && el.classList.add("hidden");

/* ─────────────────────────────────────────────────────────────────
   1. CURRENT YEAR
───────────────────────────────────────────────────────────────── */
$$("[id='currentYear']").forEach(el => {
  el.textContent = new Date().getFullYear();
});

/* ─────────────────────────────────────────────────────────────────
   2. MOBILE NAV TOGGLE
───────────────────────────────────────────────────────────────── */
(function initMobileNav() {
  const toggle = $("#navToggle");
  const nav    = $(".primary-nav");
  if (!toggle || !nav) return;

  toggle.addEventListener("click", () => {
    const isOpen = toggle.getAttribute("aria-expanded") === "true";
    toggle.setAttribute("aria-expanded", String(!isOpen));
    toggle.classList.toggle("is-open", !isOpen);
    nav.classList.toggle("is-open", !isOpen);
  });

  // Close nav on outside click
  document.addEventListener("click", e => {
    if (!toggle.contains(e.target) && !nav.contains(e.target)) {
      toggle.setAttribute("aria-expanded", "false");
      toggle.classList.remove("is-open");
      nav.classList.remove("is-open");
    }
  });

  // Close nav when a link is clicked
  $$(".nav-link", nav).forEach(link => {
    link.addEventListener("click", () => {
      toggle.setAttribute("aria-expanded", "false");
      toggle.classList.remove("is-open");
      nav.classList.remove("is-open");
    });
  });
})();

/* ─────────────────────────────────────────────────────────────────
   3. EVENTS ENGINE  (index.html only)
───────────────────────────────────────────────────────────────── */
(function initEventsEngine() {
  const container  = $("#eventsContainer");
  const emptyState = $("#emptyState");
  const loadState  = $("#loadingState");
  const countEl    = $("#eventsCount");
  const searchEl   = $("#eventSearch");
  const sortEl     = $("#sortSelect");
  if (!container) return; // not on index page

  /* ── Interested / saved events (persisted to localStorage) ── */
  const STORAGE_KEY = "campusnav_interested";
  let interested = new Set();
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) interested = new Set(JSON.parse(saved));
  } catch (_) { /* ignore */ }

  function saveInterested() {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify([...interested])); }
    catch (_) { /* ignore */ }
  }

  /* ── State ── */
  let allEvents    = [];
  let activeFilter = "all";

  /* ── Sample fallback events (used when events.json cannot be loaded) ── */
  const SAMPLE_EVENTS = [
    {
      id: "e001",
      title: "Introduction to Artificial Intelligence",
      category: "academic",
      date: "2026-06-18",
      time: "10:00 AM",
      location: "Eva Vonn, Administration Block",
      description: "An introductory seminar covering the fundamentals of AI, machine learning, and their real-world applications.",
      image: "images/1.jpg"
    },
    {
      id: "e002",
      title: "End-of-Semester Social Night",
      category: "social",
      date: "2026-06-20",
      time: "7:00 PM",
      location: "Car Pack, Main Campus",
      description: "Celebrate the end of semester with music, food, and networking with fellow students.",
      image: "images/2.jpg"
    },
    {
      id: "e003",
      title: "Inter-Faculty Football Championship",
      category: "sports",
      date: "2026-06-22",
      time: "3:00 PM",
      location: "University Sports Complex",
      description: "Cheer on your faculty team in the annual inter-faculty football tournament. All students welcome.",
      image: "images/football.jpg"
    },
    {
      id: "e004",
      title: "Web Development Bootcamp",
      category: "workshop",
      date: "2026-06-25",
      time: "9:00 AM",
      location: "Block C, C1A",
      description: "A hands-on full-day workshop covering HTML, CSS, JavaScript, and deployment with GitHub Pages.",
      image: "images/3.png"
    },
    {
      id: "e005",
      title: "Research Methodology Symposium",
      category: "academic",
      date: "2026-07-02",
      time: "11:00 AM",
      location: "Florence Onny Auditorium, G Block",
      description: "Learn best practices for academic research, citation, and data analysis from leading faculty.",
      image: "images/4.jpg"
    },
    {
      id: "e006",
      title: "Essay Writing Competition",
      category: "Academic",
      date: "2026-07-05",
      time: "2:00 PM",
      location: "Main Library, Adminitration Block",
      description: "Students gets to test thier Writing and Literacy Skills.",
      image: "images/5.jpg"
    }
  ];

  /* ── Category colour map (matches CSS variables) ── */
  const CATEGORY_COLORS = {
    academic: "#4F46E5",
    social:   "#EC4899",
    sports:   "#10B981",
    workshop: "#F59E0B"
  };

  /* ── Format date nicely ── */
  function formatDate(dateStr) {
    if (!dateStr) return "";
    const d = new Date(dateStr + "T00:00:00");
    return d.toLocaleDateString("en-GB", { weekday: "short", day: "numeric", month: "short", year: "numeric" });
  }

  /* ── Build a single event card ── */
  function buildCard(ev) {
    const isInterested = interested.has(ev.id);
    const accentColor  = CATEGORY_COLORS[ev.category] || "#8B5CF6";

    const article = document.createElement("article");
    article.className = "event-card";
    article.setAttribute("role", "listitem");
    article.setAttribute("data-id", ev.id);
    article.setAttribute("data-category", ev.category || "");
    article.setAttribute("data-date", ev.date || "");
    article.setAttribute("data-title", (ev.title || "").toLowerCase());

    article.innerHTML = `
      <img
        class="event-image"
        src="${ev.image || ""}"
        alt="${ev.location || "Event venue"}"
        loading="lazy"
        onerror="this.style.background='linear-gradient(135deg,#eef2ff,#dde3f0)';this.removeAttribute('src');"
      >
      <div class="event-content">
        <h3 class="event-title">${escHtml(ev.title || "Untitled Event")}</h3>
        <p class="event-details">
          ${ev.date ? `📅 ${formatDate(ev.date)}` : ""}
          ${ev.time ? `&nbsp; 🕐 ${escHtml(ev.time)}` : ""}
        </p>
        ${ev.location ? `<p class="event-meta">📍 ${escHtml(ev.location)}</p>` : ""}
        ${ev.description ? `<p class="event-details" style="margin-top:4px;">${escHtml(ev.description)}</p>` : ""}
        <div class="event-actions">
          <span class="event-category-badge" style="background:${accentColor}22;color:${accentColor};">
            ${escHtml(ev.category || "general")}
          </span>
          <button
            class="btn btn--outline interest-btn"
            data-id="${ev.id}"
            aria-label="${isInterested ? "Remove from" : "Add to"} interested list"
            title="${isInterested ? "Remove interest" : "I'm interested"}"
            aria-pressed="${isInterested}"
          >${isInterested ? "★ Interested" : "☆ Interested"}</button>
          <button
            class="btn btn--primary reminder-btn"
            data-id="${ev.id}"
            data-title="${escHtml(ev.title || "")}"
            aria-label="Set reminder for ${escHtml(ev.title || "this event")}"
          >🔔 Remind me</button>
        </div>
      </div>
    `;

    return article;
  }

  /** Escape HTML to prevent XSS */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  /* ── Render filtered / sorted subset ── */
  function renderEvents() {
    const query = searchEl ? searchEl.value.trim().toLowerCase() : "";

    let subset = allEvents.filter(ev => {
      const matchFilter = activeFilter === "all" || ev.category === activeFilter;
      const matchSearch = !query
        || (ev.title  || "").toLowerCase().includes(query)
        || (ev.category || "").toLowerCase().includes(query)
        || (ev.location || "").toLowerCase().includes(query);
      return matchFilter && matchSearch;
    });

    // Sort
    const sortVal = sortEl ? sortEl.value : "date-asc";
    subset.sort((a, b) => {
      if (sortVal === "date-asc")  return (a.date || "") < (b.date || "") ? -1 : 1;
      if (sortVal === "date-desc") return (a.date || "") > (b.date || "") ? -1 : 1;
      if (sortVal === "name-asc")  return (a.title || "").localeCompare(b.title || "");
      if (sortVal === "name-desc") return (b.title || "").localeCompare(a.title || "");
      return 0;
    });

    // Clear existing cards (keep non-article children intact)
    $$("article.event-card", container).forEach(el => el.remove());

    if (subset.length === 0) {
      show(emptyState);
      if (countEl) countEl.textContent = "No events found";
    } else {
      hide(emptyState);
      const frag = document.createDocumentFragment();
      subset.forEach(ev => frag.appendChild(buildCard(ev)));
      container.appendChild(frag);
      if (countEl) countEl.textContent = `Showing ${subset.length} event${subset.length !== 1 ? "s" : ""}`;
    }
  }

  /* ── Load events ── */
  async function loadEvents() {
    hide(emptyState);
    show(loadState);

    try {
      const resp = await fetch("events.json");
      if (!resp.ok) throw new Error("HTTP " + resp.status);
      const data = await resp.json();
      allEvents = Array.isArray(data) ? data : (data.events || []);
    } catch (_) {
      // Fall back to sample data silently
      allEvents = SAMPLE_EVENTS;
    } finally {
      hide(loadState);
      renderEvents();
    }
  }

  /* ── Search ── */
  if (searchEl) {
    let debounceTimer;
    searchEl.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(renderEvents, 260);
    });
  }

  const searchForm = $("#eventSearchForm");
  if (searchForm) {
    searchForm.addEventListener("submit", e => {
      e.preventDefault();
      renderEvents();
    });
  }

  /* ── Sort ── */
  if (sortEl) sortEl.addEventListener("change", renderEvents);

  /* ── Filter chips ── */
  $$(".chip").forEach(chip => {
    chip.addEventListener("click", () => {
      $$(".chip").forEach(c => c.classList.remove("chip--active"));
      chip.classList.add("chip--active");
      activeFilter = chip.dataset.filter || "all";
      renderEvents();
    });
  });

  /* ── Reminder & Interest (event delegation) ── */
  container.addEventListener("click", e => {
    // Interested button
    const intBtn = e.target.closest(".interest-btn");
    if (intBtn) {
      const id = intBtn.dataset.id;
      if (interested.has(id)) {
        interested.delete(id);
        intBtn.textContent = "☆ Interested";
        intBtn.setAttribute("aria-pressed", "false");
      } else {
        interested.add(id);
        intBtn.textContent = "★ Interested";
        intBtn.setAttribute("aria-pressed", "true");
        showToast("Added to your interested list ✓");
      }
      saveInterested();
      return;
    }

    // Reminder button
    const remBtn = e.target.closest(".reminder-btn");
    if (remBtn) {
      const title = remBtn.dataset.title || "this event";
      showToast(`⏰ Reminder set for "${title}"`);
      return;
    }
  });

  /* ── Toast notification ── */
  function showToast(message) {
    const existing = $(".reminder-toast");
    if (existing) existing.remove();

    const toast = document.createElement("div");
    toast.className = "reminder-toast";
    toast.setAttribute("role", "status");
    toast.setAttribute("aria-live", "polite");
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3200);
  }

  // Kick off
  loadEvents();
})();

/* ─────────────────────────────────────────────────────────────────
   4. CONTACT FORM  (contact.html)
───────────────────────────────────────────────────────────────── */
(function initContactForm() {
  const form     = $("#contactForm");
  const response = $("#formResponse");
  if (!form) return;

  form.addEventListener("submit", e => {
    e.preventDefault();
    const name = ($("#name", form)?.value || "").trim();
    if (response) {
      response.textContent = name
        ? `Thank you, ${name}! Your message has been received. We'll get back to you soon.`
        : "Thank you! Your message has been received. We'll get back to you soon.";
      response.style.color = "var(--color-indigo)";
    }
    form.reset();
  });
})();

/* ─────────────────────────────────────────────────────────────────
   5. CAMPUS MAP MODAL  (optional enhancement)
   If any element has data-map-venue, clicking it shows a simple
   Google Maps search in a new tab for that venue.
───────────────────────────────────────────────────────────────── */
document.addEventListener("click", e => {
  const el = e.target.closest("[data-map-venue]");
  if (!el) return;
  const venue = el.dataset.mapVenue;
  if (venue) {
    const url = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(venue)}`;
    window.open(url, "_blank", "noopener,noreferrer");
  }
});

/* ─────────────────────────────────────────────────────────────────
   6. ABOUT PAGE — plain <header> exists (no site-header class)
   Nothing extra needed; CSS handles it.
───────────────────────────────────────────────────────────────── */

/* ─────────────────────────────────────────────────────────────────
   7. SMOOTH SCROLL  for in-page anchor links
───────────────────────────────────────────────────────────────── */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
  anchor.addEventListener("click", function(e) {
    const target = document.querySelector(this.getAttribute("href"));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  });
});
