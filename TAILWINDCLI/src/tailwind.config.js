module.exports = {
  content: ["./**/*.html"],
  theme: {
    extend: {
      colors: {
        primary: "#6366F1",     // indigo
        secondary: "#14B8A6",   // teal
        accent: "#F59E0B",      // amber

        background: "#0B1220",
        surface: "#111827",

        text: "#E5E7EB",
        muted: "#9CA3AF",

        card: "#111827",
        border: "#1F2937",
      },

      boxShadow: {
        soft: "0 10px 30px rgba(0,0,0,0.25)",
      },

      borderRadius: {
        xl: "0.75rem",
        "2xl": "1.25rem",
      },

      fontFamily: {
        headline: ["Plus Jakarta Sans", "sans-serif"],
        body: ["Noto Sans", "sans-serif"],
      },
    },
  },
  plugins: [],
};