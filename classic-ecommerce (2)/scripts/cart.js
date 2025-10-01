// Cart management
class Cart {
  constructor() {
    this.items = this.loadCart()
    this.updateCartCount()
  }

  loadCart() {
    const saved = localStorage.getItem("cart")
    return saved ? JSON.parse(saved) : []
  }

  saveCart() {
    localStorage.setItem("cart", JSON.stringify(this.items))
    this.updateCartCount()
  }

  addItem(product, quantity = 1) {
    const existingItem = this.items.find((item) => item.id === product.id)

    if (existingItem) {
      existingItem.quantity += quantity
    } else {
      this.items.push({
        ...product,
        quantity: quantity,
      })
    }

    this.saveCart()
  }

  removeItem(productId) {
    this.items = this.items.filter((item) => item.id !== productId)
    this.saveCart()
  }

  updateQuantity(productId, quantity) {
    const item = this.items.find((item) => item.id === productId)
    if (item) {
      item.quantity = Math.max(1, quantity)
      this.saveCart()
    }
  }

  getTotal() {
    return this.items.reduce((total, item) => total + item.price * item.quantity, 0)
  }

  getItemCount() {
    return this.items.reduce((count, item) => count + item.quantity, 0)
  }

  updateCartCount() {
    const countElements = document.querySelectorAll("#cartCount")
    const count = this.getItemCount()
    countElements.forEach((el) => {
      el.textContent = count
      el.style.display = count > 0 ? "flex" : "none"
    })
  }

  clear() {
    this.items = []
    this.saveCart()
  }
}

// Initialize cart
const cart = new Cart()

// Mobile menu toggle
document.addEventListener("DOMContentLoaded", () => {
  const mobileMenuBtn = document.getElementById("mobileMenuBtn")
  const mainNav = document.getElementById("mainNav")

  if (mobileMenuBtn && mainNav) {
    mobileMenuBtn.addEventListener("click", () => {
      mainNav.classList.toggle("active")
    })
  }
})
