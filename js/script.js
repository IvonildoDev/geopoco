/**
 * GeoWell - Sistema de Cadastro de Poços
 * script.js - Funcionalidades principais do sistema
 */
let map;
let marker;

function initMap(lat = -23.5505, lng = -46.6333) {
    map = L.map('map').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', function (e) {
        placeMarker(e.latlng.lat, e.latlng.lng);
    });
}

function placeMarker(lat, lng) {
    if (marker) {
        map.removeLayer(marker);
    }
    marker = L.marker([lat, lng]).addTo(map);
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
    marker.bindPopup(`<a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank">Ver no Google Maps</a>`).openPopup();
}

function getCoordinates() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                map.setView([lat, lng], 13);
                placeMarker(lat, lng);
            },
            (error) => {
                alert('Erro ao obter localização: ' + error.message);
            }
        );
    } else {
        alert('Geolocalização não é suportada pelo seu navegador');
    }
}

function previewPhoto() {
    const fileInput = document.getElementById('photo');
    const preview = document.getElementById('photoPreviewContainer');
    const previewImage = document.getElementById('previewImage');
    const placeholder = document.getElementById('photoPlaceholder');

    if (fileInput.files && fileInput.files[0]) {
        const reader = new FileReader();

        reader.onload = function (e) {
            previewImage.src = e.target.result;
            previewImage.style.display = 'block';
            placeholder.style.display = 'none';
        };

        reader.readAsDataURL(fileInput.files[0]);
    }
}

function searchWells() {
    const searchTerm = document.getElementById('searchInput').value;
    const searchType = document.querySelector('input[name="searchType"]:checked') ?
        document.querySelector('input[name="searchType"]:checked').value : 'all';

    // Mostrar indicador de carregamento
    const resultsDiv = document.getElementById('searchResults');
    resultsDiv.innerHTML = '<div class="loading-indicator"><i class="fas fa-spinner fa-spin"></i> Carregando resultados...</div>';

    // Adicionar timestamp para evitar cache
    const timestamp = new Date().getTime();
    const searchUrl = `php/search.php?term=${encodeURIComponent(searchTerm)}&type=${encodeURIComponent(searchType)}&_=${timestamp}`;

    console.log("URL de busca:", searchUrl);

    fetch(searchUrl, {
        method: 'GET',
        headers: {
            'Cache-Control': 'no-cache'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Dados recebidos:', data);

            resultsDiv.innerHTML = '';

            if (data && data.length > 0) {
                data.forEach(well => {
                    console.log("Processando poço:", well);

                    // Garantir que temos os dados básicos, com fallbacks
                    const city = well.city || 'Cidade não disponível';
                    const wellName = well.wellName || 'Nome não disponível';
                    const lat = parseFloat(well.latitude) || 0;
                    const lng = parseFloat(well.longitude) || 0;

                    console.log(`Dados extraídos - Cidade: ${city}, Nome Poço: ${wellName}, Lat: ${lat}, Lng: ${lng}`);

                    let wellCard = document.createElement('div');
                    wellCard.className = 'well-card';

                    // Determinar a visualização da foto
                    let photoHtml = '';
                    if (well.photo && well.photo !== '' && well.photo !== null) {
                        // Garantir que o caminho da foto esteja correto
                        const photoPath = well.photo.startsWith('uploads/') ? well.photo : `uploads/${well.photo}`;
                        photoHtml = `
                            <div class="well-photo">
                                <img src="${photoPath}" alt="Foto do poço ${wellName}" onerror="this.onerror=null;this.src='img/no-image.png';">
                            </div>`;
                        console.log("Caminho da foto:", photoPath);
                    } else {
                        photoHtml = `
                            <div class="well-photo no-photo">
                                <i class="fas fa-camera-slash"></i>
                                <span>Sem foto</span>
                            </div>`;
                    }

                    wellCard.innerHTML = `
                        ${photoHtml}
                        <div class="well-info">
                            <h3>${wellName}</h3>
                            <p><i class="fas fa-city"></i> ${city}</p>
                            <p><i class="fas fa-map-marker-alt"></i> ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                            <div class="well-actions">
                                <button class="btn btn-primary btn-sm view-on-map-btn" onclick="showOnMap(${lat}, ${lng})">
                                    <i class="fas fa-map-marked-alt"></i> Ver no Mapa
                                </button>
                                <a href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-external-link-alt"></i> Google Maps
                                </a>
                            </div>
                        </div>
                    `;

                    resultsDiv.appendChild(wellCard);
                });
            } else {
                resultsDiv.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <p>Nenhum resultado encontrado para "${searchTerm || 'busca vazia'}"</p>
                        <small>Verifique a ortografia ou tente outro termo</small>
                    </div>`;
            }
        })
        .catch(error => {
            console.error('Erro na busca:', error);
            resultsDiv.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erro ao buscar dados: ${error.message}</p>
                    <div class="error-actions">
                        <button class="btn btn-secondary btn-sm" onclick="window.location.href='php/db_setup.php'">
                            <i class="fas fa-database"></i> Verificar Banco
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="searchWells()">
                            <i class="fas fa-sync"></i> Tentar Novamente
                        </button>
                    </div>
                </div>`;
        });
}

function showOnMap(lat, lng) {
    // Muda para a seção do mapa
    const mapNav = document.querySelector('a[data-section="mapa"]');
    if (mapNav) {
        mapNav.click(); // Simula o clique no link do mapa para mostrar a seção
    }

    // Pequeno delay para garantir que o mapa esteja visível
    setTimeout(() => {
        map.invalidateSize(); // Atualiza tamanho do mapa
        map.setView([lat, lng], 15); // Zoom mais próximo
        placeMarker(lat, lng);
    }, 100);
}

document.addEventListener('DOMContentLoaded', function () {
    // Elementos do DOM
    const wellForm = document.getElementById('wellForm');
    const photoInput = document.getElementById('photo');
    const previewImage = document.getElementById('previewImage');
    const photoPlaceholder = document.getElementById('photoPlaceholder');
    const getCoordinatesBtn = document.getElementById('getCoordinatesBtn');
    const locationStatus = document.getElementById('locationStatus');
    const latitude = document.getElementById('latitude');
    const longitude = document.getElementById('longitude');
    const navLinks = document.querySelectorAll('.main-nav a');
    const sections = document.querySelectorAll('.section-container');
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    const searchButton = document.getElementById('searchButton');
    const zoomInBtn = document.getElementById('zoomInBtn');
    const zoomOutBtn = document.getElementById('zoomOutBtn');
    const locateMeBtn = document.getElementById('locateMeBtn');
    const photoPreviewContainer = document.getElementById('photoPreviewContainer');

    // Inicializa o mapa
    initMap();

    // Navegação entre abas
    navLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetSection = this.getAttribute('data-section');

            // Remove classe ativa de todos os links e adiciona ao clicado
            navLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');

            // Esconde todas as seções e mostra a selecionada
            sections.forEach(section => section.classList.remove('active-section'));
            document.getElementById(targetSection).classList.add('active-section');

            // Se a seção for o mapa, redimensiona para garantir exibição correta
            if (targetSection === 'mapa') {
                map.invalidateSize();
            }
        });
    });

    // Toggle para menu mobile
    mobileMenuToggle.addEventListener('click', function () {
        mainNav.style.display = mainNav.style.display === 'flex' ? 'none' : 'flex';
    });

    // Evento para pré-visualizar a foto
    photoInput.addEventListener('change', previewPhoto);

    // Clicar no container de preview abre o seletor de arquivo
    photoPreviewContainer.addEventListener('click', function () {
        photoInput.click();
    });

    // Evento para obter coordenadas
    getCoordinatesBtn.addEventListener('click', getCoordinates);

    // Busca de poços
    if (searchButton) {
        searchButton.addEventListener('click', searchWells);
    }

    // Controles do mapa
    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', function () {
            map.zoomIn();
        });
    }

    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', function () {
            map.zoomOut();
        });
    }

    if (locateMeBtn) {
        locateMeBtn.addEventListener('click', getCoordinates);
    }

    // Verifica se o CSS está sendo carregado
    const cssLoaded = window.getComputedStyle(document.body).getPropertyValue('color');
    console.log('CSS Loaded:', cssLoaded);

    // Mostrar notificação se há mensagem na URL e limpar parâmetro
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get('message');
    if (message) {
        showNotification(message);

        // Remover o parâmetro 'message' da URL sem recarregar a página
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }

    // Permitir busca ao pressionar Enter no campo de busca
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                searchWells();
            }
        });
    }
});

// Função para mostrar notificação
function showNotification(message) {
    const notification = document.getElementById('notification');
    const notificationMessage = document.getElementById('notificationMessage');

    notificationMessage.textContent = message;
    notification.classList.add('show');

    setTimeout(() => {
        notification.classList.remove('show');
    }, 5000);

    // Fechar notificação ao clicar no X
    const closeBtn = notification.querySelector('.notification-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            notification.classList.remove('show');
        });
    }
}