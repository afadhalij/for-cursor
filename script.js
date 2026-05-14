/* ============================================================
 * Inganzo Ngari — Coming Soon page
 * Countdown + trilingual (EN / RW / FR) language switcher
 * ============================================================ */

(function () {
  "use strict";

  /* -------- 1. Launch date: 1 June 2026, 19:00 Africa/Kigali (UTC+2) -------- */
  // Using ISO with explicit +02:00 so it resolves identically in every browser.
  var LAUNCH_DATE = new Date("2026-06-01T19:00:00+02:00");

  /* -------- 2. Translations -------- */
  var I18N = {
    en: {
      eyebrow: "Coming Soon",
      tagline: "Where tradition meets the stage",
      subtitle:
        "A celebration of Rwandan rhythm, dance and heritage — soon to be unveiled.",
      days: "Days",
      hours: "Hours",
      minutes: "Minutes",
      seconds: "Seconds",
      launchLabel: "Curtain rises",
      launched: "We are live. Karibu!",
      rights: "All rights reserved",
      htmlLang: "en",
    },
    rw: {
      eyebrow: "Bizahita Bigaragara",
      tagline: "Aho umuco uhurira n'urubuga",
      subtitle:
        "Ibirori by'umuco nyarwanda, imbyino n'umurage — bigiye gufungurwa.",
      days: "Iminsi",
      hours: "Amasaha",
      minutes: "Iminota",
      seconds: "Amasegonda",
      launchLabel: "Umutaka uzazamuka",
      launched: "Twatangiye. Murakaza neza!",
      rights: "Uburenganzira bwose burabitswe",
      htmlLang: "rw",
    },
    fr: {
      eyebrow: "Bientôt Disponible",
      tagline: "Là où la tradition rencontre la scène",
      subtitle:
        "Une célébration du rythme, de la danse et du patrimoine rwandais — bientôt dévoilée.",
      days: "Jours",
      hours: "Heures",
      minutes: "Minutes",
      seconds: "Secondes",
      launchLabel: "Lever de rideau",
      launched: "Nous sommes en ligne. Bienvenue !",
      rights: "Tous droits réservés",
      htmlLang: "fr",
    },
  };

  /* -------- 3. Localised launch-date label -------- */
  function formattedLaunchDate(lang) {
    var locale = { en: "en-GB", rw: "rw-RW", fr: "fr-FR" }[lang] || "en-GB";
    try {
      return new Intl.DateTimeFormat(locale, {
        day: "numeric",
        month: "long",
        year: "numeric",
      }).format(LAUNCH_DATE);
    } catch (_) {
      return "1 June 2026";
    }
  }

  /* -------- 4. Language switching -------- */
  function applyLanguage(lang) {
    if (!I18N[lang]) lang = "en";
    var dict = I18N[lang];

    document.documentElement.setAttribute("lang", dict.htmlLang);
    document.documentElement.setAttribute("data-lang", lang);

    // Update text nodes flagged with [data-i18n="key"].
    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      var key = el.getAttribute("data-i18n");
      if (dict[key]) el.textContent = dict[key];
    });

    // Update the localised date suffix.
    var dateEl = document.querySelector(".launch-date-value");
    if (dateEl) dateEl.textContent = "· " + formattedLaunchDate(lang);

    // Sync language buttons.
    document.querySelectorAll(".lang-btn").forEach(function (btn) {
      var isActive = btn.getAttribute("data-lang") === lang;
      btn.classList.toggle("is-active", isActive);
      btn.setAttribute("aria-pressed", isActive ? "true" : "false");
    });

    try {
      localStorage.setItem("ing_lang", lang);
    } catch (_) {}
  }

  function initLanguage() {
    var stored = null;
    try {
      stored = localStorage.getItem("ing_lang");
    } catch (_) {}

    var initial = stored && I18N[stored] ? stored : "en";
    applyLanguage(initial);

    document.querySelectorAll(".lang-btn").forEach(function (btn) {
      btn.addEventListener("click", function () {
        applyLanguage(btn.getAttribute("data-lang"));
      });
    });
  }

  /* -------- 5. Countdown -------- */
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
    var diff = LAUNCH_DATE.getTime() - Date.now();

    if (diff <= 0) {
      if (els.days) els.days.textContent = "00";
      if (els.hours) els.hours.textContent = "00";
      if (els.minutes) els.minutes.textContent = "00";
      if (els.seconds) els.seconds.textContent = "00";
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
    var id = setInterval(function () {
      if (!tick()) clearInterval(id);
    }, 1000);
  }

  /* -------- 6. Boot -------- */
  function init() {
    var yearEl = document.getElementById("year");
    if (yearEl) yearEl.textContent = String(new Date().getFullYear());

    initLanguage();
    startCountdown();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
