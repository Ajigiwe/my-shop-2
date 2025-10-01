// Checkout page functionality
const cart = {
  // Declare the cart variable
  items: [],
  getTotal: function () {
    return this.items.reduce((total, item) => total + item.price * item.quantity, 0)
  },
  clear: function () {
    this.items = []
  },
}

document.addEventListener("DOMContentLoaded", () => {
  displayCheckoutSummary()
  setupCheckoutForm()
})

function displayCheckoutSummary() {
  const summaryItems = document.getElementById("summaryItems")

  if (cart.items.length === 0) {
    window.location.href = "cart.html"
    return
  }

  summaryItems.innerHTML = cart.items
    .map(
      (item) => `
    <div class="summary-item">
      <img src="${item.image}" alt="${item.name}" class="summary-item-image">
      <div class="summary-item-info">
        <div class="summary-item-name">${item.name}</div>
        <div class="summary-item-details">Qty: ${item.quantity} × $${item.price.toFixed(2)}</div>
      </div>
    </div>
  `,
    )
    .join("")

  updateCheckoutTotals()
}

function updateCheckoutTotals() {
  const subtotal = cart.getTotal()
  const shipping = subtotal > 500 ? 0 : 50
  const tax = subtotal * 0.08
  const total = subtotal + shipping + tax

  document.getElementById("checkoutSubtotal").textContent = `$${subtotal.toFixed(2)}`
  document.getElementById("checkoutShipping").textContent = shipping === 0 ? "FREE" : `$${shipping.toFixed(2)}`
  document.getElementById("checkoutTax").textContent = `$${tax.toFixed(2)}`
  document.getElementById("checkoutTotal").textContent = `$${total.toFixed(2)}`
}

function setupCheckoutForm() {
  const form = document.getElementById("checkoutForm")

  form.addEventListener("submit", (e) => {
    e.preventDefault()

    // Simulate order processing
    alert("Thank you for your order! This is a demo, so no actual payment was processed.")

    // Clear cart and redirect
    cart.clear()
    window.location.href = "index.html"
  })

  // Format card number input
  const cardNumber = document.getElementById("cardNumber")
  cardNumber.addEventListener("input", (e) => {
    const value = e.target.value.replace(/\s/g, "")
    const formattedValue = value.match(/.{1,4}/g)?.join(" ") || value
    e.target.value = formattedValue
  })

  // Format expiry date
  const expiry = document.getElementById("expiry")
  expiry.addEventListener("input", (e) => {
    let value = e.target.value.replace(/\D/g, "")
    if (value.length >= 2) {
      value = value.slice(0, 2) + "/" + value.slice(2, 4)
    }
    e.target.value = value
  })
}
