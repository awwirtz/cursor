(() => {
  const menuToggle = document.getElementById("menuToggle");
  const mobileMenu = document.getElementById("mobileMenu");
  const mobileLinks = document.querySelectorAll(".mobile-nav a");
  const yearNode = document.getElementById("year");
  const clockNode = document.getElementById("clock");
  const revealItems = document.querySelectorAll(".reveal");
  const serviceCards = document.querySelectorAll(".service-card");

  function openMenu() {
    if (!mobileMenu || !menuToggle) return;
    mobileMenu.hidden = false;
    mobileMenu.classList.add("open");
    menuToggle.setAttribute("aria-expanded", "true");
  }

  function closeMenu() {
    if (!mobileMenu || !menuToggle) return;
    mobileMenu.classList.remove("open");
    mobileMenu.hidden = true;
    menuToggle.setAttribute("aria-expanded", "false");
  }

  menuToggle?.addEventListener("click", () => {
    if (mobileMenu?.classList.contains("open")) {
      closeMenu();
      return;
    }
    openMenu();
  });

  mobileLinks.forEach((link) => link.addEventListener("click", closeMenu));

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeMenu();
    }
  });

  serviceCards.forEach((card) => {
    card.addEventListener("toggle", () => {
      if (!card.open) return;
      serviceCards.forEach((other) => {
        if (other !== card) other.open = false;
      });
    });
  });

  if (yearNode) {
    yearNode.textContent = String(new Date().getFullYear());
  }

  function pad(value) {
    return String(value).padStart(2, "0");
  }

  function renderClock() {
    if (!clockNode) return;
    const now = new Date();
    clockNode.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
  }

  renderClock();
  setInterval(renderClock, 1000);

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { rootMargin: "0px 0px -10% 0px", threshold: 0.15 }
    );

    revealItems.forEach((item) => observer.observe(item));
  } else {
    revealItems.forEach((item) => item.classList.add("is-visible"));
  }
})();
