import "./bootstrap";
import { usersPreferences } from "./pages/usersPreferences";

import Alpine from "alpinejs";

window.Alpine = Alpine;
window.usersPreferences = usersPreferences;
window.landingPage = function () {
	return {
		theme: document.documentElement.getAttribute("data-theme") || "light",
		init() {
			const items = Array.from(this.$root.querySelectorAll("[data-reveal]"));
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

						entry.target.classList.remove("opacity-0", "translate-y-3");
						entry.target.classList.add("opacity-100", "translate-y-0");
						observer.unobserve(entry.target);
					});
				},
				{
					threshold: 0.15,
					rootMargin: "0px 0px -50px 0px",
				}
			);

			items.forEach((item) => {
				observer.observe(item);
			});
		},
		toggleTheme() {
			this.theme = this.theme === "dark" ? "light" : "dark";
			document.documentElement.setAttribute("data-theme", this.theme);
			document.documentElement.classList.toggle("dark", this.theme === "dark");
			localStorage.setItem("theme", this.theme);
		},
	};
};

Alpine.start();
