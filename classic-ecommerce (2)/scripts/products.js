// Products page functionality
const products = [
  { id: 1, name: "Product 1", category: "Category 1", price: 99.99, image: "image1.jpg" },
  { id: 2, name: "Product 2", category: "Category 2", price: 150.0, image: "image2.jpg" },
  // Add more products as needed
]

let filteredProducts = [...products]

document.addEventListener("DOMContentLoaded", () => {
  displayProducts(products)
  setupFilters()
  setupSort()
})

function displayProducts(productsToShow) {
  const container = document.getElementById("productsGrid")
  const resultsCount = document.getElementById("resultsCount")

  if (!container) return

  resultsCount.textContent = productsToShow.length

  container.innerHTML = productsToShow
    .map(
      (product) => `
    <div class="product-card" onclick="goToProduct(${product.id})">
      <img src="${product.image}" alt="${product.name}" class="product-image">
      <div class="product-info">
        <p class="product-category">${product.category}</p>
        <h3 class="product-name">${product.name}</h3>
        <p class="product-price">$${product.price.toFixed(2)}</p>
      </div>
    </div>
  `,
    )
    .join("")
}

function setupFilters() {
  const categoryFilters = document.querySelectorAll(".category-filter")
  const priceFilters = document.querySelectorAll(".price-filter")

  categoryFilters.forEach((filter) => {
    filter.addEventListener("change", applyFilters)
  })

  priceFilters.forEach((filter) => {
    filter.addEventListener("change", applyFilters)
  })
}

function applyFilters() {
  const selectedCategories = Array.from(document.querySelectorAll(".category-filter:checked")).map((cb) => cb.value)

  const selectedPrice = document.querySelector(".price-filter:checked").value

  filteredProducts = products.filter((product) => {
    // Category filter
    if (selectedCategories.length > 0 && !selectedCategories.includes(product.category)) {
      return false
    }

    // Price filter
    if (selectedPrice !== "all") {
      if (selectedPrice === "0-100" && product.price >= 100) return false
      if (selectedPrice === "100-500" && (product.price < 100 || product.price >= 500)) return false
      if (selectedPrice === "500-1000" && (product.price < 500 || product.price >= 1000)) return false
      if (selectedPrice === "1000+" && product.price < 1000) return false
    }

    return true
  })

  applySorting()
}

function setupSort() {
  const sortSelect = document.getElementById("sortSelect")
  sortSelect.addEventListener("change", applySorting)
}

function applySorting() {
  const sortValue = document.getElementById("sortSelect").value
  const sorted = [...filteredProducts]

  switch (sortValue) {
    case "price-low":
      sorted.sort((a, b) => a.price - b.price)
      break
    case "price-high":
      sorted.sort((a, b) => b.price - a.price)
      break
    case "name":
      sorted.sort((a, b) => a.name.localeCompare(b.name))
      break
  }

  displayProducts(sorted)
}

function goToProduct(id) {
  window.location.href = `product-detail.html?id=${id}`
}
