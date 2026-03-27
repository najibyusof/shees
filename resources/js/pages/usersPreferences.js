export function usersPreferences(config = {}) {
    return {
        prefsKey: config.prefsKey || "ui.users.preferences.v1",
        density: config.defaults?.density || "comfortable",
        defaultSort: config.defaults?.defaultSort || "created_at",
        defaultDirection: config.defaults?.defaultDirection || "desc",
        saving: false,
        visibleColumns: {
            id: true,
            name: true,
            email: true,
            created_at: true,
            ...(config.defaults?.visibleColumns || {}),
        },
        serverPrefs: config.serverPrefs || null,
        saveUrl: config.saveUrl || "",
        csrfToken: config.csrfToken || "",

        init() {
            if (this.serverPrefs) {
                this.density = this.serverPrefs.density ?? this.density;
                this.defaultSort =
                    this.serverPrefs.defaultSort ?? this.defaultSort;
                this.defaultDirection =
                    this.serverPrefs.defaultDirection ?? this.defaultDirection;
                this.visibleColumns = {
                    ...this.visibleColumns,
                    ...(this.serverPrefs.visibleColumns || {}),
                };
            }

            const stored = localStorage.getItem(this.prefsKey);

            if (stored) {
                try {
                    const parsed = JSON.parse(stored);
                    this.density = parsed.density ?? this.density;
                    this.defaultSort = parsed.defaultSort ?? this.defaultSort;
                    this.defaultDirection =
                        parsed.defaultDirection ?? this.defaultDirection;
                    this.visibleColumns = {
                        ...this.visibleColumns,
                        ...(parsed.visibleColumns || {}),
                    };
                } catch (error) {
                    localStorage.removeItem(this.prefsKey);
                }
            }

            const url = new URL(window.location.href);
            const hasSort = url.searchParams.has("sort");
            const hasDirection = url.searchParams.has("direction");

            if (!hasSort && !hasDirection && this.defaultSort) {
                url.searchParams.set("sort", this.defaultSort);
                url.searchParams.set("direction", this.defaultDirection);
                window.location.replace(url.toString());
            }
        },

        savePrefs() {
            localStorage.setItem(
                this.prefsKey,
                JSON.stringify({
                    density: this.density,
                    defaultSort: this.defaultSort,
                    defaultDirection: this.defaultDirection,
                    visibleColumns: this.visibleColumns,
                }),
            );
        },

        async savePrefsToServer() {
            this.saving = true;
            this.savePrefs();

            try {
                const response = await fetch(this.saveUrl, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": this.csrfToken,
                    },
                    body: JSON.stringify({
                        density: this.density,
                        defaultSort: this.defaultSort,
                        defaultDirection: this.defaultDirection,
                        visibleColumns: this.visibleColumns,
                    }),
                });

                if (!response.ok) {
                    throw new Error("Unable to save preferences");
                }

                const payload = await response.json();

                this.$dispatch(
                    "toast",
                    payload.toast || {
                        type: "success",
                        title: "Preferences Updated",
                        message: "Your settings were saved successfully.",
                    },
                );
            } catch (error) {
                this.$dispatch("toast", {
                    type: "error",
                    title: "Save Failed",
                    message: "We could not save your preferences right now.",
                });
            } finally {
                this.saving = false;
            }
        },

        resetPrefs() {
            this.density = "comfortable";
            this.defaultSort = "created_at";
            this.defaultDirection = "desc";
            this.visibleColumns = {
                id: true,
                name: true,
                email: true,
                created_at: true,
            };
            this.savePrefs();
        },

        isColumnVisible(column) {
            return this.visibleColumns[column] !== false;
        },

        rowDensityClass() {
            return this.density === "compact" ? "h-10" : "h-12";
        },
    };
}
