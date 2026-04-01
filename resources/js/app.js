import "./bootstrap";
import { usersPreferences } from "./pages/usersPreferences";
import { Chart, registerables } from "chart.js";

import Alpine from "alpinejs";

Chart.register(...registerables);
window.Chart = Chart;

window.Alpine = Alpine;
window.usersPreferences = usersPreferences;
window.landingPage = function () {
    return {
        theme: document.documentElement.getAttribute("data-theme") || "light",
        mobileMenu: false,
        desktopMenuMediaQuery: null,
        desktopMenuHandler: null,
        init() {
            this.desktopMenuMediaQuery =
                window.matchMedia("(min-width: 768px)");
            this.desktopMenuHandler = (event) => {
                if (event.matches) {
                    this.mobileMenu = false;
                }
            };

            this.desktopMenuHandler(this.desktopMenuMediaQuery);
            if (
                typeof this.desktopMenuMediaQuery.addEventListener ===
                "function"
            ) {
                this.desktopMenuMediaQuery.addEventListener(
                    "change",
                    this.desktopMenuHandler,
                );
            } else if (
                typeof this.desktopMenuMediaQuery.addListener === "function"
            ) {
                this.desktopMenuMediaQuery.addListener(this.desktopMenuHandler);
            }

            const items = Array.from(
                this.$root.querySelectorAll("[data-reveal]"),
            );
            if (!items.length) {
                return;
            }

            if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
                items.forEach((item) => {
                    item.classList.remove("opacity-0", "translate-y-3");
                    item.classList.add("opacity-100", "translate-y-0");
                });
                return;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.remove(
                            "opacity-0",
                            "translate-y-3",
                        );
                        entry.target.classList.add(
                            "opacity-100",
                            "translate-y-0",
                        );
                        observer.unobserve(entry.target);
                    });
                },
                {
                    threshold: 0.15,
                    rootMargin: "0px 0px -50px 0px",
                },
            );

            items.forEach((item) => {
                observer.observe(item);
            });
        },
        toggleMobileMenu() {
            this.mobileMenu = !this.mobileMenu;
        },
        closeMobileMenu() {
            this.mobileMenu = false;
        },
        toggleTheme() {
            this.theme = this.theme === "dark" ? "light" : "dark";
            document.documentElement.setAttribute("data-theme", this.theme);
            document.documentElement.classList.toggle(
                "dark",
                this.theme === "dark",
            );
            localStorage.setItem("theme", this.theme);
        },
    };
};

Alpine.start();
