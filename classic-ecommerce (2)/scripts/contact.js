// Contact form functionality
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("contactForm")

  form.addEventListener("submit", (e) => {
    e.preventDefault()

    // Simulate form submission
    const formContainer = document.querySelector(".contact-form")
    const successMessage = document.getElementById("formSuccess")

    formContainer.style.display = "none"
    successMessage.style.display = "block"

    // Reset form after 3 seconds
    setTimeout(() => {
      form.reset()
      formContainer.style.display = "block"
      successMessage.style.display = "none"
    }, 3000)
  })
})
