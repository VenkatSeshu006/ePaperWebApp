document.addEventListener('DOMContentLoaded', function() {
    // Check if editionData is available
    if (typeof window.editionData === 'undefined') {
        console.error('Edition data not found');
        return;
    }
    
    // Access data from window.editionData
    const { imagePaths, editionId, imageIds, editionTitle, baseUrl, availableDates, editionMap, totalPages, currentUrl } = window.editionData;

    // Get DOM elements with proper fallback
    const thumbnails = document.querySelectorAll('.page-thumbnail');
    const images = document.querySelectorAll('.full-image');
    const zoomInBtn = document.querySelector('.zoom-in');
    const zoomOutBtn = document.querySelector('.zoom-out');
    const fullScreenBtn = document.querySelector('.full-screen');
    const prevBtn = document.querySelector('.prev-button');
    const nextBtn = document.querySelector('.next-button');
    const pageCounter = document.querySelector('.page-counter');
    const pagination = document.querySelector('.pagination');
    const pdfDownloadBtn = document.querySelector('.pdf-download');
    const imageContainer = document.querySelector('.image-container');
    const clipButton = document.querySelector('.clip-button');
    
    // Initialize modals safely
    let popupModal, clipPreviewModal;
    try {
        if (typeof bootstrap !== 'undefined') {
            const popupElement = document.getElementById('imagePopup');
            const clipElement = document.getElementById('clipPreviewModal');
            if (popupElement) popupModal = new bootstrap.Modal(popupElement);
            if (clipElement) clipPreviewModal = new bootstrap.Modal(clipElement);
        }
    } catch (e) {
        console.warn('Bootstrap modals not available:', e);
    }
    
    const popupImage = document.getElementById('popupImage');
    
    // State variables
    let currentIndex = 0;
    let zoomLevel = 1;
    let translateX = 0;
    let translateY = 0;
    let isDragging = false;
    let startX, startY;
    const pagesPerGroup = 5;
    let cropper = null;

    // Initialize based on URL page parameter
    const urlParams = new URLSearchParams(window.location.search);
    const page = parseInt(urlParams.get('page')) || 1;
    currentIndex = Math.max(0, Math.min(totalPages - 1, page - 1));
    
    // Initialize interface
    updateImage(currentIndex);
    renderPagination(currentIndex);
    updateNavigationButtons();

    // === EVENT LISTENERS ===

    // Thumbnail clicks
    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', function(e) {
            e.preventDefault();
            updateImage(index);
            renderPagination(index);
            updateURL(index + 1);
        });
    });

    // Navigation button event listeners
    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            navigateImage(-1);
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            navigateImage(1);
        });
    }

    // Zoom functionality
    if (zoomInBtn) {
        zoomInBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!cropper) {
                zoomLevel = Math.min(zoomLevel + 0.25, 3);
                updateZoom();
                showNotification('Zoomed to ' + Math.round(zoomLevel * 100) + '%');
            }
        });
    }
    
    if (zoomOutBtn) {
        zoomOutBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!cropper) {
                zoomLevel = Math.max(zoomLevel - 0.25, 0.5);
                updateZoom();
                showNotification('Zoomed to ' + Math.round(zoomLevel * 100) + '%');
            }
        });
    }

    // Full-screen functionality
    if (fullScreenBtn) {
        fullScreenBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const viewer = document.querySelector('.main-viewer') || document.body;
            
            if (document.fullscreenElement) {
                document.exitFullscreen().then(() => {
                    showNotification('Exited fullscreen');
                }).catch(err => {
                    console.error('Fullscreen exit failed:', err);
                    showNotification('Fullscreen exit failed', 'error');
                });
            } else {
                viewer.requestFullscreen().then(() => {
                    showNotification('Entered fullscreen mode');
                }).catch(err => {
                    console.error('Fullscreen failed:', err);
                    showNotification('Fullscreen not supported', 'error');
                });
            }
        });
    }

    // PDF Download functionality
    if (pdfDownloadBtn) {
        pdfDownloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            generatePDF();
        });
    }

    // Clip button functionality
    if (clipButton) {
        clipButton.addEventListener('click', (e) => {
            e.preventDefault();
            handleClipTool();
        });
    }

    // Image container interactions
    if (imageContainer) {
        // Mouse wheel zoom
        imageContainer.addEventListener('wheel', (e) => {
            if (e.ctrlKey) {
                e.preventDefault();
                if (e.deltaY < 0) {
                    zoomLevel = Math.min(zoomLevel + 0.1, 3);
                } else {
                    zoomLevel = Math.max(zoomLevel - 0.1, 0.5);
                }
                updateZoom();
            }
        });

        // Zoom panning with mouse
        imageContainer.addEventListener('mousedown', (e) => {
            if (zoomLevel <= 1 || cropper || e.button !== 0) return;
            isDragging = true;
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
            imageContainer.style.cursor = 'grabbing';
        });

        imageContainer.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            updateZoom();
        });

        imageContainer.addEventListener('mouseup', () => {
            isDragging = false;
            imageContainer.style.cursor = 'default';
        });

        imageContainer.addEventListener('mouseleave', () => {
            isDragging = false;
            imageContainer.style.cursor = 'default';
        });

        // Touch swipe navigation
        let touchStartX = 0;
        let touchStartY = 0;
        
        imageContainer.addEventListener('touchstart', e => {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        });
        
        imageContainer.addEventListener('touchmove', e => {
            if (!e.touches || !e.touches[0]) return;
            const touchMoveX = e.touches[0].clientX;
            const touchMoveY = e.touches[0].clientY;
            const deltaX = touchStartX - touchMoveX;
            const deltaY = touchStartY - touchMoveY;
            
            if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
                e.preventDefault();
            }
        }, { passive: false });

        imageContainer.addEventListener('touchend', e => {
            if (!e.changedTouches || !e.changedTouches[0]) return;
            const touchEndX = e.changedTouches[0].clientX;
            const touchEndY = e.changedTouches[0].clientY;
            const swipeDistanceX = touchStartX - touchEndX;
            const swipeDistanceY = touchStartY - touchEndY;

            if (Math.abs(swipeDistanceX) > Math.abs(swipeDistanceY) && Math.abs(swipeDistanceX) > 50) {
                if (swipeDistanceX > 0) navigateImage(1);
                else navigateImage(-1);
            }
        });
    }

    // Pagination click handler
    if (pagination) {
        pagination.addEventListener('click', function(e) {
            const link = e.target.closest('.page-link');
            if (!link) return;
            e.preventDefault();
            const page = parseInt(link.getAttribute('data-page'));
            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                const newIndex = page - 1;
                updateImage(newIndex);
                renderPagination(newIndex);
                updateURL(page);
            }
        });
    }

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch(e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                navigateImage(-1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                navigateImage(1);
                break;
            case 'Home':
                e.preventDefault();
                updateImage(0);
                renderPagination(0);
                updateURL(1);
                break;
            case 'End':
                e.preventDefault();
                updateImage(totalPages - 1);
                renderPagination(totalPages - 1);
                updateURL(totalPages);
                break;
            case 'Escape':
                e.preventDefault();
                if (cropper) {
                    handleClipTool(); // Toggle off
                }
                if (document.fullscreenElement) {
                    document.exitFullscreen();
                }
                break;
            case '+':
            case '=':
                if (e.ctrlKey) {
                    e.preventDefault();
                    zoomLevel = Math.min(zoomLevel + 0.25, 3);
                    updateZoom();
                }
                break;
            case '-':
                if (e.ctrlKey) {
                    e.preventDefault();
                    zoomLevel = Math.max(zoomLevel - 0.25, 0.5);
                    updateZoom();
                }
                break;
        }
    });

    // === CORE FUNCTIONS ===

    function updateImage(index) {
        if (index < 0 || index >= totalPages) return;
        
        // Update images
        images.forEach((img, i) => {
            img.classList.toggle('active', i === index);
        });
        
        // Update thumbnails
        thumbnails.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });
        
        currentIndex = index;
        
        // Update page counter
        if (pageCounter) {
            pageCounter.textContent = `Page ${currentIndex + 1} of ${totalPages}`;
        }
        
        // Reset zoom when changing pages
        resetZoom();
        updateNavigationButtons();
    }

    function navigateImage(direction) {
        const newIndex = currentIndex + direction;
        if (newIndex < 0 || newIndex >= totalPages) return;
        
        updateImage(newIndex);
        renderPagination(newIndex);
        updateURL(newIndex + 1);
    }

    function updateNavigationButtons() {
        if (prevBtn) {
            prevBtn.disabled = currentIndex <= 0;
        }
        if (nextBtn) {
            nextBtn.disabled = currentIndex >= totalPages - 1;
        }
    }

    function updateZoom() {
        if (cropper) return;
        
        const activeImage = document.querySelector('.full-image.active');
        if (!activeImage) return;

        if (zoomLevel === 1) {
            activeImage.style.transform = 'none';
            translateX = 0;
            translateY = 0;
        } else {
            // Constrain translation to keep image visible
            const containerRect = imageContainer.getBoundingClientRect();
            const imageRect = activeImage.getBoundingClientRect();
            
            const maxTranslateX = Math.max(0, (imageRect.width * zoomLevel - containerRect.width) / 2);
            const maxTranslateY = Math.max(0, (imageRect.height * zoomLevel - containerRect.height) / 2);
            
            translateX = Math.max(-maxTranslateX, Math.min(maxTranslateX, translateX));
            translateY = Math.max(-maxTranslateY, Math.min(maxTranslateY, translateY));
            
            activeImage.style.transform = `scale(${zoomLevel}) translate(${translateX}px, ${translateY}px)`;
        }
        
        activeImage.style.transformOrigin = 'center center';
        
        // Update zoom buttons state
        if (zoomInBtn) zoomInBtn.disabled = zoomLevel >= 3;
        if (zoomOutBtn) zoomOutBtn.disabled = zoomLevel <= 0.5;
    }

    function resetZoom() {
        zoomLevel = 1;
        translateX = 0;
        translateY = 0;
        updateZoom();
    }

    function renderPagination(index) {
        if (!pagination) return;
        
        const currentPage = index + 1;
        const isMobile = window.matchMedia("(max-width: 576px)").matches;
        let html = '';
        
        if (isMobile) {
            // Mobile pagination
            if (currentPage > 1) {
                html += `<a href="#" class="page-link" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a>`;
            }
            html += `<span class="page-link active">${currentPage}</span>`;
            if (currentPage < totalPages) {
                html += `<a href="#" class="page-link" data-page="${currentPage + 1}"><i class="fas fa-chevron-right"></i></a>`;
            }
        } else {
            // Desktop pagination
            const startPage = Math.floor((currentPage - 1) / pagesPerGroup) * pagesPerGroup + 1;
            const endPage = Math.min(startPage + pagesPerGroup - 1, totalPages);
            
            if (currentPage > 1) {
                html += `<a href="#" class="page-link" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a>`;
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = (i === currentPage) ? 'active' : '';
                html += `<a href="#" class="page-link ${activeClass}" data-page="${i}">${i}</a>`;
            }
            
            if (currentPage < totalPages) {
                html += `<a href="#" class="page-link" data-page="${currentPage + 1}"><i class="fas fa-chevron-right"></i></a>`;
            }
        }
        
        pagination.innerHTML = html;
    }

    function updateURL(page) {
        const url = new URL(window.location);
        url.searchParams.set('id', editionId);
        url.searchParams.set('page', page);
        window.history.pushState({}, '', url);
    }

    // === ADVANCED FEATURES ===

    function handleClipTool() {
        const activeImage = document.querySelector('.full-image.active');
        if (!activeImage) return;

        if (cropper) {
            // Stop cropping
            cropper.destroy();
            cropper = null;
            if (zoomInBtn) zoomInBtn.disabled = false;
            if (zoomOutBtn) zoomOutBtn.disabled = false;
            if (clipButton) {
                clipButton.innerHTML = '<i class="fa-solid fa-scissors"></i> Clip';
                clipButton.classList.remove('active');
            }
            showNotification('Clip tool disabled');
            return;
        }

        // Check if Cropper is available
        if (typeof Cropper === 'undefined') {
            showNotification('Cropper.js not loaded', 'error');
            return;
        }

        try {
            cropper = new Cropper(activeImage, {
                zoomable: false,
                movable: false,
                rotatable: false,
                scalable: false,
                viewMode: 1,
                autoCrop: true,
                dragMode: 'move',
                cropBoxMovable: true,
                cropBoxResizable: true,
                ready() {
                    showNotification('Clip tool active - select area to crop');
                    addCropButtons();
                }
            });

            if (zoomInBtn) zoomInBtn.disabled = true;
            if (zoomOutBtn) zoomOutBtn.disabled = true;
            if (clipButton) {
                clipButton.innerHTML = '<i class="fa-solid fa-scissors"></i> Stop';
                clipButton.classList.add('active');
            }
        } catch (error) {
            console.error('Cropper error:', error);
            showNotification('Error starting clip tool', 'error');
        }
    }

    function addCropButtons() {
        const cropBox = document.querySelector('.cropper-crop-box');
        if (!cropBox) return;

        const buttonContainer = document.createElement('div');
        buttonContainer.className = 'crop-button-container';
        buttonContainer.style.cssText = `
            position: absolute; top: -50px; left: 0; right: 0;
            display: flex; gap: 8px; justify-content: center; z-index: 1002;
        `;

        const saveBtn = document.createElement('button');
        saveBtn.className = 'cropper-share-btn';
        saveBtn.innerHTML = '<i class="fas fa-save"></i> Save';
        saveBtn.onclick = saveCurrentCrop;

        const cancelBtn = document.createElement('button');
        cancelBtn.className = 'cropper-cancel-btn';
        cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
        cancelBtn.onclick = handleClipTool;

        buttonContainer.appendChild(saveBtn);
        buttonContainer.appendChild(cancelBtn);
        cropBox.appendChild(buttonContainer);
    }

    function saveCurrentCrop() {
        if (!cropper) return;

        const canvas = cropper.getCroppedCanvas();
        if (!canvas) {
            showNotification('No crop area selected', 'error');
            return;
        }

        canvas.toBlob(blob => {
            const formData = new FormData();
            formData.append('image', blob, 'cropped.jpg');
            formData.append('edition_id', editionId);
            formData.append('image_id', imageIds[currentIndex] || currentIndex + 1);

            fetch('save_clip.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Clip saved successfully!', 'success');
                    if (clipPreviewModal && data.clip_path) {
                        const clipUrl = `${baseUrl}/clips.php?id=${data.clip_id}`;
                        const clipImage = document.getElementById('clipPreviewImage');
                        const clipLink = document.getElementById('clipPreviewLink');
                        
                        if (clipImage) clipImage.src = data.clip_path;
                        if (clipLink) clipLink.value = clipUrl;
                        
                        clipPreviewModal.show();
                    }
                } else {
                    showNotification('Failed to save clip: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error saving clip:', error);
                showNotification('Network error saving clip', 'error');
            })
            .finally(() => {
                handleClipTool(); // Turn off clip tool
            });
        }, 'image/jpeg', 0.9);
    }

    async function generatePDF() {
        if (typeof window.jspdf === 'undefined') {
            showNotification('PDF library not loaded', 'error');
            return;
        }

        if (!imagePaths || imagePaths.length === 0) {
            showNotification('No images to convert', 'error');
            return;
        }

        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageWidth = 210;
        const pageHeight = 297;

        showPDFLoadingAnimation(0);

        try {
            for (let i = 0; i < imagePaths.length; i++) {
                const img = new Image();
                img.crossOrigin = 'Anonymous';
                img.src = imagePaths[i];
                
                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    setTimeout(() => reject(new Error('Image load timeout')), 10000);
                });

                const imgWidth = img.width;
                const imgHeight = img.height;
                const scale = Math.min(pageWidth / imgWidth, pageHeight / imgHeight);
                const scaledWidth = imgWidth * scale;
                const scaledHeight = imgHeight * scale;

                if (i > 0) doc.addPage();
                doc.addImage(
                    img, 
                    'JPEG', 
                    (pageWidth - scaledWidth) / 2, 
                    (pageHeight - scaledHeight) / 2, 
                    scaledWidth, 
                    scaledHeight
                );
                
                showPDFLoadingAnimation(i + 1);
            }

            const filename = `${editionTitle.replace(/\s+/g, '-')}-edition.pdf`;
            doc.save(filename);
            showNotification('PDF downloaded successfully!', 'success');
        } catch (error) {
            console.error('PDF generation error:', error);
            showNotification('Error generating PDF', 'error');
        } finally {
            hidePDFLoadingAnimation();
        }
    }

    function showPDFLoadingAnimation(current = 0, total = totalPages) {
        if (pdfDownloadBtn) {
            pdfDownloadBtn.disabled = true;
            pdfDownloadBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Generating ${current}/${total}`;
        }
    }

    function hidePDFLoadingAnimation() {
        if (pdfDownloadBtn) {
            pdfDownloadBtn.disabled = false;
            pdfDownloadBtn.innerHTML = '<i class="fas fa-download"></i> PDF';
        }
    }

    // === UTILITY FUNCTIONS ===

    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px; z-index: 10000;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff'};
            color: white; padding: 12px 20px; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    // Archive functionality
    if (typeof $ !== 'undefined') {
        const datepickerContainer = $("#datepicker-container");
        $("#archive-button").click(function() {
            if (typeof $.datepicker === 'undefined') {
                showNotification('Archive feature requires jQuery UI', 'error');
                return;
            }
            
            datepickerContainer.toggle();
            const isMobile = window.matchMedia("(max-width: 768px)").matches;
            
            if (datepickerContainer.is(':visible')) {
                datepickerContainer.datepicker({
                    beforeShowDay: function(date) {
                        const formattedDate = $.datepicker.formatDate('yy-mm-dd', date);
                        return [availableDates.includes(formattedDate), ''];
                    },
                    onSelect: function(dateText) {
                        const selectedEditionId = editionMap[dateText];
                        if (selectedEditionId) {
                            window.location.href = 'index.php?id=' + selectedEditionId;
                        }
                        datepickerContainer.hide();
                    },
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        });
    }

    // Copy clip link functionality
    document.getElementById('copyClipLinkBtn')?.addEventListener('click', function() {
        const linkInput = document.getElementById('clipPreviewLink');
        if (linkInput) {
            linkInput.select();
            navigator.clipboard.writeText(linkInput.value).then(() => {
                showNotification('Link copied to clipboard!', 'success');
            }).catch(err => {
                console.error('Failed to copy:', err);
                showNotification('Failed to copy link', 'error');
            });
        }
    });

    // Social sharing functionality
    document.querySelectorAll('.social-share-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const platform = this.getAttribute('data-platform');
            const url = this.getAttribute('data-url') || currentUrl;
            shareOnPlatform(platform, url);
        });
    });

    function shareOnPlatform(platform, url) {
        const encodedUrl = encodeURIComponent(url);
        const title = encodeURIComponent(editionTitle);
        
        const shareUrls = {
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
            twitter: `https://x.com/intent/tweet?url=${encodedUrl}&text=${title}`,
            whatsapp: `https://api.whatsapp.com/send?text=${title} ${encodedUrl}`,
            linkedin: `https://www.linkedin.com/shareArticle?mini=true&url=${encodedUrl}&title=${title}`,
            telegram: `https://t.me/share/url?url=${encodedUrl}&text=${title}`,
            email: `mailto:?subject=${title}&body=Check out this article: ${encodedUrl}`
        };

        if (platform === 'print') {
            window.print();
        } else if (shareUrls[platform]) {
            window.open(shareUrls[platform], '_blank', 'width=600,height=400');
        }
    }

    // Initialize everything
    console.log('E-Paper viewer initialized successfully');
    showNotification('E-Paper viewer loaded', 'success');
});
