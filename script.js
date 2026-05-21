/* ============================================================
 * Inganzo Ngari — Coming Soon page
 * Loads content from content.json (editable via admin panel)
 * with hard-coded defaults as a fallback. Renders countdown
 * and switches between English / Kinyarwanda / French.
 * ============================================================ */

(function () {
  "use strict";

  /* -------- 1. Hard-coded fallbacks (used if content.json is missing) -------- */
  var DEFAULTS = {
    launchDate: "2026-06-01T19:00:00+02:00",
    email: "info@inganzongari.com",
    social: { instagram: "#", facebook: "#", youtube: "#", x: "#" },
    slideshow: {
      intervalMs: 6000,
      fadeMs: 2200,
      slides: [
        "assets/slides/01-troupe-portrait.webp",
        "assets/slides/02-women-yellow.webp",
        "assets/slides/03-women-pink.webp",
        "assets/slides/04-warriors-leap.webp",
        "assets/slides/05-women-purple.webp"
      ]
    },
    i18n: {
      en: {
        eyebrow: "Coming Soon",
        tagline: "Where tradition meets the stage",
        subtitle:
          "A celebration of Rwandan rhythm, dance and heritage \u2014 soon to be unveiled.",
        days: "Days", hours: "Hours", minutes: "Minutes", seconds: "Seconds",
        launchLabel: "Curtain rises",
        launched: "We are live. Karibu!",
        rights: "All rights reserved",
      },
      rw: {
        eyebrow: "Bizahita Bigaragara",
        tagline: "Aho umuco uhurira n'urubuga",
        subtitle:
          "Ibirori by'umuco nyarwanda, imbyino n'umurage \u2014 bigiye gufungurwa.",
        days: "Iminsi", hours: "Amasaha", minutes: "Iminota", seconds: "Amasegonda",
        launchLabel: "Umutaka uzazamuka",
        launched: "Twatangiye. Murakaza neza!",
        rights: "Uburenganzira bwose burabitswe",
      },
      fr: {
        eyebrow: "Bient\u00f4t Disponible",
        tagline: "L\u00e0 o\u00f9 la tradition rencontre la sc\u00e8ne",
        subtitle:
          "Une c\u00e9l\u00e9bration du rythme, de la danse et du patrimoine rwandais \u2014 bient\u00f4t d\u00e9voil\u00e9e.",
        days: "Jours", hours: "Heures", minutes: "Minutes", seconds: "Secondes",
        launchLabel: "Lever de rideau",
        launched: "Nous sommes en ligne. Bienvenue !",
        rights: "Tous droits r\u00e9serv\u00e9s",
      },
    },
  };

  var STATE = {
    launchDate: new Date(DEFAULTS.launchDate),
    i18n: DEFAULTS.i18n,
    lang: "en",
  };

  /* -------- 2. Locale helpers -------- */
  // Browsers ship inconsistent (or no) Kinyarwanda month names, so we
  // format the RW date manually and only fall back to Intl for EN/FR.
  var RW_MONTHS = [
    "Mutarama", "Gashyantare", "Werurwe", "Mata",
    "Gicurasi", "Kamena", "Nyakanga", "Kanama",
    "Nzeli", "Ukwakira", "Ugushyingo", "Ukuboza",
  ];

  function formattedLaunchDate(lang) {
    if (lang === "rw") {
      var d = STATE.launchDate;
      return d.getDate() + " " + RW_MONTHS[d.getMonth()] + " " + d.getFullYear();
    }
    var locale = { en: "en-GB", fr: "fr-FR" }[lang] || "en-GB";
    try {
      return new Intl.DateTimeFormat(locale, {
        day: "numeric", month: "long", year: "numeric",
      }).format(STATE.launchDate);
    } catch (_) {
      return STATE.launchDate.toDateString();
    }
  }

  function deepMerge(base, override) {
    if (!override || typeof override !== "object") return base;
    var out = Array.isArray(base) ? base.slice() : Object.assign({}, base);
    Object.keys(override).forEach(function (k) {
      if (
        override[k] && typeof override[k] === "object" && !Array.isArray(override[k]) &&
        out[k] && typeof out[k] === "object" && !Array.isArray(out[k])
      ) {
        out[k] = deepMerge(out[k], override[k]);
      } else if (override[k] !== undefined && override[k] !== null && override[k] !== "") {
        out[k] = override[k];
      }
    });
    return out;
  }

  /* -------- 3. Render content into DOM -------- */
  function applyStaticContent(content) {
    var emailEl = document.querySelector(".contact");
    if (emailEl && content.email) {
      emailEl.textContent = content.email;
      emailEl.setAttribute("href", "mailto:" + content.email);
    }

    if (content.social) {
      var map = { instagram: 0, facebook: 1, youtube: 2, x: 3 };
      var anchors = document.querySelectorAll(".socials a");
      Object.keys(map).forEach(function (k) {
        var a = anchors[map[k]];
        if (!a) return;
        var url = content.social[k];
        if (url && url !== "#") a.setAttribute("href", url);
      });
    }
  }

  function applyLanguage(lang) {
    if (!STATE.i18n[lang]) lang = "en";
    STATE.lang = lang;
    var dict = STATE.i18n[lang];

    document.documentElement.setAttribute("lang", lang);
    document.documentElement.setAttribute("data-lang", lang);

    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      var key = el.getAttribute("data-i18n");
      if (dict[key]) el.textContent = dict[key];
    });

    var dateEl = document.querySelector(".launch-date-value");
    if (dateEl) dateEl.textContent = "\u00b7 " + formattedLaunchDate(lang);

    document.querySelectorAll(".lang-btn").forEach(function (btn) {
      var isActive = btn.getAttribute("data-lang") === lang;
      btn.classList.toggle("is-active", isActive);
      btn.setAttribute("aria-pressed", isActive ? "true" : "false");
    });

    try { localStorage.setItem("ing_lang", lang); } catch (_) {}
  }

  function initLanguage() {
    var stored = null;
    try { stored = localStorage.getItem("ing_lang"); } catch (_) {}
    var initial = stored && STATE.i18n[stored] ? stored : "rw";
    applyLanguage(initial);

    document.querySelectorAll(".lang-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        applyLanguage(btn.getAttribute("data-lang"));
      });
    });
  }

  /* -------- 4. Countdown -------- */
  var els = {
    days: document.getElementById("cd-days"),
    hours: document.getElementById("cd-hours"),
    minutes: document.getElementById("cd-minutes"),
    seconds: document.getElementById("cd-seconds"),
    msg: document.getElementById("launched-msg"),
    countdown: document.getElementById("countdown"),
  };

  function pad(n) {
    n = Math.max(0, Math.floor(n));
    return n < 10 ? "0" + n : "" + n;
  }

  function tick() {
    var diff = STATE.launchDate.getTime() - Date.now();
    if (diff <= 0) {
      ["days", "hours", "minutes", "seconds"].forEach(function (k) {
        if (els[k]) els[k].textContent = "00";
      });
      if (els.countdown) els.countdown.classList.add("is-launched");
      if (els.msg) els.msg.hidden = false;
      return false;
    }
    var s = Math.floor(diff / 1000);
    var d = Math.floor(s / 86400);
    var h = Math.floor((s % 86400) / 3600);
    var m = Math.floor((s % 3600) / 60);
    var sec = s % 60;
    if (els.days) els.days.textContent = pad(d);
    if (els.hours) els.hours.textContent = pad(h);
    if (els.minutes) els.minutes.textContent = pad(m);
    if (els.seconds) els.seconds.textContent = pad(sec);
    return true;
  }

  function startCountdown() {
    if (!tick()) return;
    var id = setInterval(function () { if (!tick()) clearInterval(id); }, 1000);
  }

  /* -------- 4b. Slideshow background -------- */
  function startSlideshow(config) {
    var container = document.getElementById("bg-slideshow");
    if (!container) return;
    container.innerHTML = "";

    var slides = (config && Array.isArray(config.slides) ? config.slides : [])
      .map(function (s) { return String(s).trim(); })
      .filter(function (s) { return s.length > 0; });
    if (slides.length === 0) return;

    var intervalMs = Math.max(1500, parseInt(config.intervalMs, 10) || 6000);
    var fadeMs     = Math.max(200,  parseInt(config.fadeMs, 10)     || 2200);

    container.style.setProperty("--slide-interval", intervalMs + "ms");
    container.style.setProperty("--slide-fade",     fadeMs + "ms");

    // Preload images so cross-fade is buttery smooth.
    slides.forEach(function (src) {
      var im = new Image();
      im.src = src + (src.indexOf("?") === -1 ? "?_=" + Date.now() : "");
    });

    var nodes = slides.map(function (src) {
      var d = document.createElement("div");
      d.className = "slide";
      d.style.backgroundImage = "url('" + src.replace(/'/g, "%27") + "')";
      container.appendChild(d);
      return d;
    });

    var idx = 0;
    nodes[0].classList.add("is-active");
    if (nodes.length < 2) return;

    setInterval(function () {
      var current = nodes[idx];
      idx = (idx + 1) % nodes.length;
      var next = nodes[idx];

      // Restart Ken Burns animation on the incoming slide.
      next.style.animation = "none";
      void next.offsetWidth; // reflow
      next.style.animation = "";

      next.classList.add("is-active");
      // Let the next slide fade in fully before removing the previous one's
      // active class — avoids any flash of pure background between transitions.
      setTimeout(function () { current.classList.remove("is-active"); }, fadeMs);
    }, intervalMs);
  }

  /* -------- 5. Load content.json (cache-bust so admin edits show right away) -------- */
  function loadContent() {
    var url = "content.json?_=" + Date.now();
    return fetch(url, { cache: "no-store" })
      .then(function (r) { return r.ok ? r.json() : null; })
      .catch(function () { return null; })
      .then(function (loaded) {
        var merged = deepMerge(DEFAULTS, loaded || {});
        if (merged.launchDate) {
          var d = new Date(merged.launchDate);
          if (!isNaN(d.getTime())) STATE.launchDate = d;
        }
        if (merged.i18n) STATE.i18n = merged.i18n;
        applyStaticContent(merged);
        startSlideshow(merged.slideshow || DEFAULTS.slideshow);
      });
  }

  /* -------- 6. Boot -------- */
  function init() {
    var yearEl = document.getElementById("year");
    if (yearEl) yearEl.textContent = String(new Date().getFullYear());

    loadContent().then(function () {
      initLanguage();
      startCountdown();
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
