document.addEventListener("DOMContentLoaded", () => {
  const mobileMenuButton = document.getElementById("mobileMenuButton");
  const closeMenu = document.getElementById("closeMenu");
  const mainMenu = document.getElementById("mainMenu");

  if (mobileMenuButton && mainMenu) {
    mobileMenuButton.addEventListener("click", () => {
      mainMenu.classList.add("active");
    });
  }

  if (closeMenu && mainMenu) {
    closeMenu.addEventListener("click", () => {
      mainMenu.classList.remove("active");
    });
  }
});
