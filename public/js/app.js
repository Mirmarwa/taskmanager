// Fade-in effect on page load
document.addEventListener("DOMContentLoaded", () => {
    document.body.classList.add("page-loaded");
});

// Auto-hide flash messages after 3 seconds
setTimeout(() => {
    document.querySelectorAll(".alert").forEach(alert => {
        alert.classList.remove("show");
        alert.classList.add("fade");
    });
}, 3000);

// Card hover animation
document.querySelectorAll(".card").forEach(card => {
    card.addEventListener("mouseenter", () => {
        card.style.transform = "translateY(-4px)";
        card.style.boxShadow = "0 12px 30px rgba(0,0,0,0.08)";
    });

    card.addEventListener("mouseleave", () => {
        card.style.transform = "translateY(0)";
        card.style.boxShadow = "";
    });
});
// Button hover micro-interaction
document.querySelectorAll(".btn").forEach(btn => {
    btn.addEventListener("mouseenter", () => {
        btn.style.transform = "translateY(-1px)";
    });

    btn.addEventListener("mouseleave", () => {
        btn.style.transform = "translateY(0)";
    });
});
