// Slider functionality
const sliders = {
  1: { currentIndex: 0, totalSlides: 3 },
  2: { currentIndex: 0, totalSlides: 2 },
};

// Product data
const products = {
  1: { name: "Chanel No. 5 - Floral Elegance", price: 1250000, size: "50ml" },
  2: { name: "Sauvage - Woody Masculine", price: 950000, size: "60ml" },
};

// Checkout state
let currentProductId = 1;
let deliveryOption = "pickup";
let shippingCost = 15000;
let shippingType = "regular";
let currentLocation = null;
let selectedAddress = null;
let gpsMethod = "gps"; // 'gps', 'ip', or 'city'

// City data for Indonesia
const cities = [
  { id: "bogor", name: "Bogor", province: "Jawa Barat" },
  { id: "jakarta", name: "Jakarta", province: "DKI Jakarta" },
  { id: "bandung", name: "Bandung", province: "Jawa Barat" },
  { id: "depok", name: "Depok", province: "Jawa Barat" },
  { id: "tangerang", name: "Tangerang", province: "Banten" },
  { id: "bekasi", name: "Bekasi", province: "Jawa Barat" },
  { id: "surabaya", name: "Surabaya", province: "Jawa Timur" },
  { id: "semarang", name: "Semarang", province: "Jawa Tengah" },
  { id: "yogyakarta", name: "Yogyakarta", province: "DI Yogyakarta" },
  { id: "malang", name: "Malang", province: "Jawa Timur" },
  { id: "medan", name: "Medan", province: "Sumatera Utara" },
  { id: "palembang", name: "Palembang", province: "Sumatera Selatan" },
  { id: "makassar", name: "Makassar", province: "Sulawesi Selatan" },
  { id: "balikpapan", name: "Balikpapan", province: "Kalimantan Timur" },
  { id: "denpasar", name: "Denpasar", province: "Bali" },
];

// Simulated location data for each city
const locationData = {
  bogor: [
    {
      name: "Bogor Trade Mall",
      address: "Jl. Raya Tajur No. 101, Bogor",
      distance: "1.2 km",
    },
    {
      name: "Botani Square",
      address: "Jl. Raya Pajajaran, Bogor",
      distance: "0.8 km",
    },
    {
      name: "Plaza Bogor",
      address: "Jl. Kapten Muslihat No. 8, Bogor",
      distance: "2.1 km",
    },
    {
      name: "Cibinong City Mall",
      address: "Jl. Raya Mayor Oking, Bogor",
      distance: "5.3 km",
    },
    {
      name: "Permata Bogor Residence",
      address: "Jl. Suryakencana, Bogor",
      distance: "3.7 km",
    },
  ],
  jakarta: [
    {
      name: "Grand Indonesia",
      address: "Jl. M.H. Thamrin No.1, Jakarta Pusat",
      distance: "0.5 km",
    },
    {
      name: "Plaza Senayan",
      address: "Jl. Asia Afrika No.8, Jakarta Selatan",
      distance: "1.2 km",
    },
    {
      name: "Central Park Mall",
      address: "Jl. Letjen S. Parman, Jakarta Barat",
      distance: "2.5 km",
    },
  ],
  bandung: [
    {
      name: "Paris Van Java",
      address: "Jl. Sukajadi, Bandung",
      distance: "0.8 km",
    },
    { name: "Braga", address: "Jl. Braga, Bandung", distance: "1.5 km" },
    {
      name: "Setiabudi",
      address: "Jl. Dr. Setiabudi, Bandung",
      distance: "2.1 km",
    },
  ],
};

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
  // Initialize product sliders
  initializeSliders();

  // Initialize event listeners
  initializeEventListeners();

  // Initialize city grid
  populateCityGrid();

  // Pre-load Bogor locations
  setTimeout(() => {
    loadCityLocations("bogor");
  }, 500);
});

// Initialize product sliders
function initializeSliders() {
  // Update all sliders
  for (const productId in sliders) {
    updateSlider(parseInt(productId));
  }
}

// Initialize event listeners
function initializeEventListeners() {
  // Filter functionality
  document.querySelectorAll(".filter-btn").forEach((button) => {
    button.addEventListener("click", function () {
      document
        .querySelectorAll(".filter-btn")
        .forEach((btn) => btn.classList.remove("active"));
      this.classList.add("active");

      const category = this.textContent;
      filterProducts(category);
    });
  });

  // Like functionality
  document.querySelectorAll(".fa-heart, .far.fa-heart").forEach((icon) => {
    icon.addEventListener("click", function () {
      if (this.classList.contains("far")) {
        this.classList.remove("far");
        this.classList.add("fas");
        this.style.color = "#ed4956";
      } else {
        this.classList.remove("fas");
        this.classList.add("far");
        this.style.color = "";
      }
    });
  });

  // Search functionality for products
  const searchBar = document.querySelector(".search-bar");
  searchBar.addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      searchProducts(this.value);
    }
  });

  // Search functionality for GPS
  document
    .getElementById("gps-search")
    .addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        searchAddress();
      }
    });

  // Initialize order summary
  updateOrderSummary();
}

// Populate city grid
function populateCityGrid() {
  const cityGrid = document.getElementById("city-grid");
  if (!cityGrid) return;

  cityGrid.innerHTML = "";

  cities.forEach((city) => {
    const cityBtn = document.createElement("div");
    cityBtn.className = "city-btn";
    cityBtn.innerHTML = `<div class="city-name">${city.name}</div>`;
    cityBtn.onclick = () => selectCity(city);
    cityGrid.appendChild(cityBtn);
  });
}

// Slider functions
function nextSlide(productId) {
  const slider = sliders[productId];
  if (slider.currentIndex < slider.totalSlides - 1) {
    slider.currentIndex++;
  } else {
    slider.currentIndex = 0;
  }
  updateSlider(productId);
}

function prevSlide(productId) {
  const slider = sliders[productId];
  if (slider.currentIndex > 0) {
    slider.currentIndex--;
  } else {
    slider.currentIndex = slider.totalSlides - 1;
  }
  updateSlider(productId);
}

function updateSlider(productId) {
  const slider = sliders[productId];
  const sliderContainer = document.getElementById(`slider${productId}`);
  const indicators = document.getElementById(`indicators${productId}`).children;

  if (!sliderContainer) return;

  sliderContainer.style.transform = `translateX(-${slider.currentIndex * 100}%)`;

  for (let i = 0; i < indicators.length; i++) {
    if (i === slider.currentIndex) {
      indicators[i].classList.add("active");
    } else {
      indicators[i].classList.remove("active");
    }
  }
}

// Toggle product description
function toggleDescription(productId) {
  const fullDescription = document.getElementById(
    `full-description${productId}`,
  );
  const moreText = document.querySelector(`#product${productId} .more-text`);

  if (!fullDescription || !moreText) return;

  if (fullDescription.style.display === "none") {
    fullDescription.style.display = "block";
    moreText.textContent = " lebih sedikit";
  } else {
    fullDescription.style.display = "none";
    moreText.textContent = "selengkapnya";
  }
}

// Open checkout card
function openCheckout(productId) {
  currentProductId = productId;
  const product = products[productId];

  if (!product) return;

  // Update product info in checkout form
  const productNameElement = document.getElementById("checkout-product-name");
  const productPriceElement = document.getElementById("checkout-product-price");
  const productSizeElement = document.getElementById("checkout-product-size");
  const summaryProductPriceElement = document.getElementById(
    "summary-product-price",
  );

  if (productNameElement) productNameElement.textContent = product.name;
  if (productPriceElement)
    productPriceElement.textContent = `Rp ${product.price.toLocaleString()}`;
  if (productSizeElement) productSizeElement.textContent = product.size;
  if (summaryProductPriceElement)
    summaryProductPriceElement.textContent = `Rp ${product.price.toLocaleString()}`;

  // Reset form
  document.getElementById("customer-name").value = "";
  document.getElementById("customer-phone").value = "";
  document.getElementById("customer-address").value = "";
  document.getElementById("gps-search").value = "";

  // Reset delivery option
  deliveryOption = "pickup";
  document.getElementById("pickup-option").classList.add("active");
  document.getElementById("delivery-option").classList.remove("active");
  document.getElementById("shipping-section").style.display = "none";
  document.getElementById("shipping-row").style.display = "none";

  // Reset shipping
  shippingCost = 15000;
  shippingType = "regular";

  // Reset address input
  toggleAddressInput("manual");

  // Clear GPS results
  const gpsResults = document.getElementById("gps-results");
  if (gpsResults)
    gpsResults.innerHTML =
      '<div class="gps-loading"><i class="fas fa-spinner spinner"></i>Memuat data lokasi...</div>';

  document.getElementById("gps-status").style.display = "none";
  document.getElementById("accuracy-warning").style.display = "none";

  // Update total
  updateOrderSummary();

  // Show checkout card
  document.getElementById("checkout-overlay").style.display = "block";
  document.getElementById("checkout-card").style.display = "block";
}

// Close checkout card
function closeCheckout() {
  document.getElementById("checkout-overlay").style.display = "none";
  document.getElementById("checkout-card").style.display = "none";
}

// Select delivery option
function selectDeliveryOption(option) {
  deliveryOption = option;

  // Update UI
  document.getElementById("pickup-option").classList.remove("active");
  document.getElementById("delivery-option").classList.remove("active");

  if (option === "pickup") {
    document.getElementById("pickup-option").classList.add("active");
    document.getElementById("shipping-section").style.display = "none";
    document.getElementById("shipping-row").style.display = "none";
  } else {
    document.getElementById("delivery-option").classList.add("active");
    document.getElementById("shipping-section").style.display = "block";
    document.getElementById("shipping-row").style.display = "flex";
  }

  updateOrderSummary();
}

// Toggle address input method
function toggleAddressInput(method) {
  // Update button states
  document.getElementById("manual-btn").classList.remove("active");
  document.getElementById("gps-btn").classList.remove("active");

  // Show/hide input containers
  document.getElementById("manual-address").classList.remove("active");
  document.getElementById("gps-address").classList.remove("active");

  if (method === "manual") {
    document.getElementById("manual-btn").classList.add("active");
    document.getElementById("manual-address").classList.add("active");
    document.getElementById("customer-address").required = true;
  } else {
    document.getElementById("gps-btn").classList.add("active");
    document.getElementById("gps-address").classList.add("active");
    document.getElementById("customer-address").required = false;

    // Auto-detect location when switching to GPS mode
    setTimeout(() => {
      detectLocation();
    }, 300);
  }
}

// Select GPS method
function selectGPSMethod(method) {
  gpsMethod = method;

  // Update UI
  document
    .querySelectorAll(".gps-method-btn")
    .forEach((btn) => btn.classList.remove("active"));
  event.currentTarget.classList.add("active");

  // Show/hide city selection
  const citySelection = document.getElementById("city-selection");
  if (method === "city") {
    citySelection.style.display = "block";
    // Don't auto-detect for city selection
  } else {
    citySelection.style.display = "none";
    // Auto-detect based on method
    detectLocation();
  }
}

// Select city
function selectCity(city) {
  // Update UI
  document
    .querySelectorAll(".city-btn")
    .forEach((btn) => btn.classList.remove("active"));
  event.currentTarget.classList.add("active");

  // Show location for selected city
  const statusElement = document.getElementById("gps-status");
  statusElement.style.display = "flex";

  document.getElementById("gps-location-name").textContent =
    `Kota ${city.name}`;
  document.getElementById("gps-location-address").textContent =
    `Provinsi ${city.province}`;
  document.getElementById("gps-location-source").textContent =
    "Sumber: Pilihan Manual";

  // Store selected address
  selectedAddress = `${city.name}, ${city.province}`;

  // Load locations for this city
  loadCityLocations(city.id);

  // Show accuracy warning
  document.getElementById("accuracy-warning").style.display = "flex";
  document.getElementById("warning-text").textContent =
    "Lokasi berdasarkan kota yang dipilih. Periksa alamat sebelum digunakan.";
}

// Detect location using selected method
function detectLocation() {
  const statusElement = document.getElementById("gps-status");
  const resultsElement = document.getElementById("gps-results");

  if (!statusElement || !resultsElement) return;

  // Show loading state
  statusElement.style.display = "flex";
  document.getElementById("gps-location-name").textContent =
    "Mendeteksi lokasi...";
  document.getElementById("gps-location-address").textContent =
    "Sedang mengambil data...";
  document.getElementById("gps-location-source").textContent =
    `Sumber: ${gpsMethod === "gps" ? "GPS Device" : "IP Location"}`;

  resultsElement.innerHTML =
    '<div class="gps-loading"><i class="fas fa-spinner spinner"></i>Mendeteksi lokasi Anda...</div>';

  if (gpsMethod === "gps") {
    detectWithGPS();
  } else if (gpsMethod === "ip") {
    detectWithIP();
  }
}

// Detect location using GPS
function detectWithGPS() {
  // Check if browser supports Geolocation
  if (!navigator.geolocation) {
    showLocationError(
      "Browser tidak mendukung geolocation",
      "Coba gunakan metode IP Location",
    );
    return;
  }

  // Get current position
  navigator.geolocation.getCurrentPosition(
    // Success callback
    function (position) {
      const latitude = position.coords.latitude;
      const longitude = position.coords.longitude;
      const accuracy = position.coords.accuracy; // Accuracy in meters

      // Store location
      currentLocation = { lat: latitude, lng: longitude, accuracy: accuracy };

      // Determine city based on coordinates
      const detectedCity = getCityFromCoordinates(latitude, longitude);

      // Update status
      const statusElement = document.getElementById("gps-status");
      document.getElementById("gps-location-name").textContent =
        detectedCity.name;
      document.getElementById("gps-location-address").textContent =
        `Koordinat: ${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
      document.getElementById("gps-location-source").textContent =
        `Sumber: GPS Device (Akurasi: ±${Math.round(accuracy)} meter)`;

      // Store selected address
      selectedAddress = `${detectedCity.name}, ${detectedCity.province}`;

      // Show accuracy warning if accuracy is poor
      const warningElement = document.getElementById("accuracy-warning");
      if (accuracy > 1000) {
        // More than 1km accuracy
        warningElement.style.display = "flex";
        document.getElementById("warning-text").textContent =
          `Akurasi GPS rendah (±${Math.round(accuracy / 1000)} km). Periksa alamat sebelum digunakan.`;
      } else if (accuracy > 100) {
        // More than 100m accuracy
        warningElement.style.display = "flex";
        document.getElementById("warning-text").textContent =
          `Akurasi GPS sedang (±${Math.round(accuracy)} meter). Periksa alamat sebelum digunakan.`;
      } else {
        warningElement.style.display = "none";
      }

      // Load locations for detected city
      loadCityLocations(detectedCity.id);
    },
    // Error callback
    function (error) {
      let errorMessage = "Gagal mendapatkan lokasi GPS";
      let suggestion = "Coba gunakan metode IP Location atau pilih kota manual";

      switch (error.code) {
        case error.PERMISSION_DENIED:
          errorMessage = "Izin lokasi GPS ditolak";
          suggestion =
            "Izinkan akses lokasi di browser Anda atau gunakan metode lain";
          break;
        case error.POSITION_UNAVAILABLE:
          errorMessage = "GPS tidak tersedia";
          suggestion = "Periksa koneksi GPS Anda atau gunakan metode lain";
          break;
        case error.TIMEOUT:
          errorMessage = "Timeout mendapatkan lokasi GPS";
          suggestion = "Coba lagi atau gunakan metode lain";
          break;
      }

      showLocationError(errorMessage, suggestion);

      // Auto-fallback to IP method if GPS fails
      setTimeout(() => {
        selectGPSMethod("ip");
        detectLocation();
      }, 2000);
    },
    // Options
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0,
    },
  );
}

// Detect location using IP address
function detectWithIP() {
  // In a real app, you would call an IP geolocation API
  // For demo, we'll simulate it

  // Simulate API call delay
  setTimeout(() => {
    // Get user's approximate location based on IP
    const ipCity = getCityFromIP();

    // Update status
    const statusElement = document.getElementById("gps-status");
    document.getElementById("gps-location-name").textContent = ipCity.name;
    document.getElementById("gps-location-address").textContent =
      `Lokasi berdasarkan IP Anda`;
    document.getElementById("gps-location-source").textContent =
      "Sumber: IP Location (Estimasi)";

    // Store selected address
    selectedAddress = `${ipCity.name}, ${ipCity.province}`;

    // Show accuracy warning
    document.getElementById("accuracy-warning").style.display = "flex";
    document.getElementById("warning-text").textContent =
      "Lokasi berdasarkan IP mungkin kurang akurat. Periksa dan sesuaikan jika perlu.";

    // Load locations for detected city
    loadCityLocations(ipCity.id);
  }, 1500);
}

// Get city from coordinates (simplified for demo)
function getCityFromCoordinates(lat, lng) {
  // This is a simplified version. In real app, use reverse geocoding API

  // Bogor coordinates approx: -6.5971, 106.8060
  if (lat > -6.7 && lat < -6.5 && lng > 106.7 && lng < 106.9) {
    return { id: "bogor", name: "Bogor", province: "Jawa Barat" };
  }
  // Jakarta coordinates approx: -6.2088, 106.8456
  else if (lat > -6.3 && lat < -6.1 && lng > 106.7 && lng < 107.0) {
    return { id: "jakarta", name: "Jakarta", province: "DKI Jakarta" };
  }
  // Bandung coordinates approx: -6.9175, 107.6191
  else if (lat > -7.0 && lat < -6.8 && lng > 107.5 && lng < 107.8) {
    return { id: "bandung", name: "Bandung", province: "Jawa Barat" };
  }
  // Default to Bogor (since you mentioned you're in Bogor)
  else {
    return { id: "bogor", name: "Bogor", province: "Jawa Barat" };
  }
}

// Get city from IP (simulated)
function getCityFromIP() {
  // In a real app, this would call an API like ipinfo.io, ipapi.co, etc.
  // For demo, we'll return a city based on probability

  // Since you mentioned you're in Bogor, we'll prioritize Bogor
  const cities = ["bogor", "jakarta", "bandung"];
  const randomCity = cities[Math.floor(Math.random() * cities.length)];

  if (randomCity === "bogor") {
    return { id: "bogor", name: "Bogor", province: "Jawa Barat" };
  } else if (randomCity === "jakarta") {
    return { id: "jakarta", name: "Jakarta", province: "DKI Jakarta" };
  } else {
    return { id: "bandung", name: "Bandung", province: "Jawa Barat" };
  }
}

// Load locations for a city
function loadCityLocations(cityId) {
  const resultsElement = document.getElementById("gps-results");
  if (!resultsElement) return;

  // Show loading
  resultsElement.innerHTML =
    '<div class="gps-loading"><i class="fas fa-spinner spinner"></i>Memuat tempat terdekat...</div>';

  // Simulate API delay
  setTimeout(() => {
    let results = locationData[cityId] || [];

    // If no specific data for city, show general results
    if (results.length === 0) {
      // Combine results from all cities
      for (const city in locationData) {
        results = results.concat(locationData[city]);
      }
      results = results.slice(0, 8); // Limit results
    }

    // Display results
    if (results.length > 0) {
      let html = "";
      results.forEach((place) => {
        html += `
                    <div class="gps-result-item" onclick="selectGPSLocation('${place.name}', '${place.address}')">
                        <div class="gps-result-name">${place.name}</div>
                        <div class="gps-result-address">${place.address}</div>
                        <div class="gps-result-distance">${place.distance}</div>
                    </div>
                `;
      });
      resultsElement.innerHTML = html;
    } else {
      resultsElement.innerHTML =
        '<div class="gps-loading">Tidak ditemukan tempat terdekat.</div>';
    }
  }, 1000);
}

// Search address
function searchAddress() {
  const searchTerm = document.getElementById("gps-search").value.trim();
  const resultsElement = document.getElementById("gps-results");

  if (!resultsElement) return;

  if (searchTerm === "") {
    // If no search term, reload current city locations
    if (selectedAddress && selectedAddress.includes("Bogor")) {
      loadCityLocations("bogor");
    } else {
      // Combine results from all cities
      let allResults = [];
      for (const city in locationData) {
        allResults = allResults.concat(locationData[city]);
      }
      allResults = allResults.slice(0, 10);

      displaySearchResults(allResults);
    }
    return;
  }

  // Show loading
  resultsElement.innerHTML =
    '<div class="gps-loading"><i class="fas fa-spinner spinner"></i>Mencari...</div>';

  // Simulate search delay
  setTimeout(() => {
    let results = [];

    // Search in all location data
    for (const city in locationData) {
      const cityResults = locationData[city].filter(
        (place) =>
          place.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
          place.address.toLowerCase().includes(searchTerm.toLowerCase()),
      );
      results = results.concat(cityResults);
    }

    // If no results found in location data, show simulated results
    if (results.length === 0) {
      results = [
        {
          name: `${searchTerm} Mall`,
          address: `Jl. ${searchTerm} Raya, Bogor`,
          distance: "3.5 km",
        },
        {
          name: `Pasar ${searchTerm}`,
          address: `Jl. ${searchTerm} No. 15, Bogor`,
          distance: "2.8 km",
        },
        {
          name: `Perumahan ${searchTerm} Indah`,
          address: `Jl. ${searchTerm} Permai, Bogor`,
          distance: "4.2 km",
        },
      ];
    }

    // Limit results
    results = results.slice(0, 8);

    displaySearchResults(results);
  }, 800);
}

// Display search results
function displaySearchResults(results) {
  const resultsElement = document.getElementById("gps-results");
  if (!resultsElement) return;

  if (results.length > 0) {
    let html = "";
    results.forEach((place) => {
      html += `
                <div class="gps-result-item" onclick="selectGPSLocation('${place.name}', '${place.address}')">
                    <div class="gps-result-name">${place.name}</div>
                    <div class="gps-result-address">${place.address}</div>
                    <div class="gps-result-distance">${place.distance}</div>
                </div>
            `;
    });
    resultsElement.innerHTML = html;
  } else {
    resultsElement.innerHTML =
      '<div class="gps-loading">Tidak ditemukan hasil untuk pencarian ini.</div>';
  }
}

// Show location error
function showLocationError(message, suggestion = "") {
  const statusElement = document.getElementById("gps-status");
  const resultsElement = document.getElementById("gps-results");

  if (!statusElement || !resultsElement) return;

  statusElement.style.display = "flex";
  document.getElementById("gps-location-name").textContent =
    "Gagal Mendeteksi Lokasi";
  document.getElementById("gps-location-address").textContent = message;
  document.getElementById("gps-location-source").textContent = suggestion;

  resultsElement.innerHTML = `
        <div class="gps-loading">
            <i class="fas fa-exclamation-triangle"></i>
            <div>${message}</div>
            ${suggestion ? `<div style="margin-top: 5px; font-size: 12px;">${suggestion}</div>` : ""}
        </div>
    `;

  // Show accuracy warning
  document.getElementById("accuracy-warning").style.display = "flex";
  document.getElementById("warning-text").textContent =
    "Gagal mendapatkan lokasi akurat. Silakan pilih kota manual atau gunakan input alamat manual.";
}

// Select GPS location from results
function selectGPSLocation(name, address) {
  // Update status
  document.getElementById("gps-location-name").textContent = name;
  document.getElementById("gps-location-address").textContent = address;
  document.getElementById("gps-location-source").textContent =
    "Sumber: Pencarian Manual";
  document.getElementById("gps-status").style.display = "flex";

  // Store selected address
  selectedAddress = address;

  // Show accuracy warning
  document.getElementById("accuracy-warning").style.display = "flex";
  document.getElementById("warning-text").textContent =
    "Alamat dipilih dari hasil pencarian. Periksa sebelum digunakan.";

  // Highlight selected item
  const items = document.querySelectorAll("#gps-results .gps-result-item");
  items.forEach((item) => {
    item.style.backgroundColor = "";
    item.style.border = "";
  });

  event.currentTarget.style.backgroundColor = "#f0f8ff";
  event.currentTarget.style.border = "1px solid #0095f6";
}

// Use GPS location
function useGPSLocation() {
  if (selectedAddress) {
    document.getElementById("customer-address").value = selectedAddress;
    // Switch back to manual mode to show the address
    toggleAddressInput("manual");
  }
}

// Select shipping option
function selectShipping(type, cost) {
  shippingType = type;
  shippingCost = cost;

  // Update UI
  const shippingItems = document.querySelectorAll(".shipping-item");
  shippingItems.forEach((item) => item.classList.remove("active"));
  event.currentTarget.classList.add("active");

  updateOrderSummary();
}

// Update order summary
function updateOrderSummary() {
  const product = products[currentProductId];
  let total = product.price;

  // Add shipping cost if delivery option is selected
  if (deliveryOption === "delivery") {
    const shippingCostElement = document.getElementById("summary-shipping");
    if (shippingCostElement)
      shippingCostElement.textContent = `Rp ${shippingCost.toLocaleString()}`;
    total += shippingCost;
  }

  const totalElement = document.getElementById("summary-total");
  if (totalElement) totalElement.textContent = `Rp ${total.toLocaleString()}`;
}

// Filter products (simulated)
function filterProducts(category) {
  console.log(`Filtering products by category: ${category}`);
  // In a real app, this would filter the product list
}

// Search products (simulated)
function searchProducts(query) {
  console.log(`Searching products for: ${query}`);
  alert(`Mencari produk: ${query}`);
  // In a real app, this would search the product list
}

// Submit order
function submitOrder() {
  const name = document.getElementById("customer-name").value;
  const phone = document.getElementById("customer-phone").value;
  const address = document.getElementById("customer-address").value;
  const product = products[currentProductId];

  // Validation
  if (!name || !phone || !address) {
    alert("Harap lengkapi semua data yang diperlukan!");
    return;
  }

  // Calculate total
  let total = product.price;
  let deliveryInfo = "";

  if (deliveryOption === "pickup") {
    deliveryInfo = "Order di tempat (ambil di gerai/pameran)";
  } else {
    total += shippingCost;
    deliveryInfo = `Dikirim (${shippingType}) - Rp ${shippingCost.toLocaleString()}`;
  }

  // Prepare WhatsApp message
  const message =
    `Halo, saya ingin memesan:\n\n` +
    `Produk: ${product.name}\n` +
    `Harga: Rp ${product.price.toLocaleString()}\n` +
    `Metode: ${deliveryInfo}\n` +
    `Total: Rp ${total.toLocaleString()}\n\n` +
    `Data Diri:\n` +
    `Nama: ${name}\n` +
    `WhatsApp: ${phone}\n` +
    `Alamat: ${address}`;

  // Encode message for WhatsApp URL
  const encodedMessage = encodeURIComponent(message);
  const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;

  // Show success message
  const successMessage = document.getElementById("success-message");
  successMessage.innerHTML =
    `Pesanan Anda telah berhasil dibuat!<br><br>` +
    `Produk: ${product.name}<br>` +
    `Total: Rp ${total.toLocaleString()}<br><br>` +
    `Kami akan menghubungi Anda via WhatsApp untuk konfirmasi lebih lanjut.`;

  // Open WhatsApp in new tab
  window.open(whatsappUrl, "_blank");

  // Show success modal
  closeCheckout();
  document.getElementById("success-modal").style.display = "flex";
}

// Close success modal
function closeSuccessModal() {
  document.getElementById("success-modal").style.display = "none";
}
