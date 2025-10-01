// Authentication handling
document.addEventListener("DOMContentLoaded", () => {
  // Update cart count
  updateCartCount()

  // Login form handling
  const loginForm = document.getElementById("loginForm")
  if (loginForm) {
    loginForm.addEventListener("submit", handleLogin)
  }

  // Signup form handling
  const signupForm = document.getElementById("signupForm")
  if (signupForm) {
    signupForm.addEventListener("submit", handleSignup)
  }
})

function handleLogin(e) {
  e.preventDefault()

  const email = document.getElementById("email").value
  const password = document.getElementById("password").value
  const remember = document.getElementById("remember").checked

  // Basic validation
  if (!email || !password) {
    alert("Please fill in all fields")
    return
  }

  // In a real application, you would send this to a server
  // For now, we'll just simulate a successful login
  const user = {
    email: email,
    loggedIn: true,
    loginTime: new Date().toISOString(),
  }

  // Store user data
  if (remember) {
    localStorage.setItem("user", JSON.stringify(user))
  } else {
    sessionStorage.setItem("user", JSON.stringify(user))
  }

  // Show success message
  alert("Login successful! Welcome back.")

  // Redirect to home page
  window.location.href = "index.html"
}

function handleSignup(e) {
  e.preventDefault()

  const firstName = document.getElementById("firstName").value
  const lastName = document.getElementById("lastName").value
  const email = document.getElementById("email").value
  const password = document.getElementById("password").value
  const confirmPassword = document.getElementById("confirmPassword").value
  const terms = document.getElementById("terms").checked

  // Validation
  if (!firstName || !lastName || !email || !password || !confirmPassword) {
    alert("Please fill in all fields")
    return
  }

  if (password.length < 8) {
    alert("Password must be at least 8 characters long")
    return
  }

  if (password !== confirmPassword) {
    alert("Passwords do not match")
    return
  }

  if (!terms) {
    alert("Please agree to the Terms & Conditions")
    return
  }

  // In a real application, you would send this to a server
  // For now, we'll just simulate a successful signup
  const user = {
    firstName: firstName,
    lastName: lastName,
    email: email,
    loggedIn: true,
    signupTime: new Date().toISOString(),
  }

  // Store user data
  localStorage.setItem("user", JSON.stringify(user))

  // Show success message
  alert(`Welcome ${firstName}! Your account has been created successfully.`)

  // Redirect to home page
  window.location.href = "index.html"
}

function updateCartCount() {
  const cart = JSON.parse(localStorage.getItem("cart")) || []
  const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0)
  const cartCountElement = document.getElementById("cartCount")
  if (cartCountElement) {
    cartCountElement.textContent = totalItems
  }
}
