document.addEventListener('DOMContentLoaded', function() {
    // Access data from window.editionData
    const { imagePaths, editionId, imageIds, editionTitle, baseUrl, availableDates, editionMap, totalPages, currentUrl } = window.editionData;

    const thumbnails = document.querySelectorAll('.preview-link');
    const images = document.querySelectorAll('.full-image');
    const zoomInBtn = document.querySelector('.zoom-in');
    const zoomOutBtn = document.querySelector('.zoom-out');
    const fullScreenBtn = document.querySelector('.full-screen');
    const prevBtn = document.querySelector('.prev-button');
    const nextBtn = document.querySelector('.next-button');
    const imagePrevBtn = document.querySelector('.image-prev-button');
    const imageNextBtn = document.querySelector('.image-next-button');
    const pageCounter = document.querySelector('.page-counter');
    const pagination = document.querySelector('.pagination');
    const popupModal = new bootstrap.Modal(document.getElementById('imagePopup'));
    const clipPreviewModal = new bootstrap.Modal(document.getElementById('clipPreviewModal'));
    const popupImage = document.getElementById('popupImage');
    const pdfDownloadBtn = document.querySelector('.pdf-download');
    const imageContainer = document.querySelector('.image-container');
    let currentIndex = 0;
    let zoomLevel = 1;
    let translateX = 0; // New: For panning
    let translateY = 0; // New: For panning
    let isDragging = false; // New: Track dragging state
    let startX, startY; // New: Track drag start position
    const pagesPerGroup = 5;

    // Cropper.js variable
    let cropper = null;

    // Initialize based on URL page parameter
    const urlParams = new URLSearchParams(window.location.search);
    const page = parseInt(urlParams.get('page')) || 1;
    currentIndex = Math.max(0, Math.min(totalPages - 1, page - 1));
    updateImage(currentIndex);
    renderPagination(currentIndex);

    // Thumbnail clicks
    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', function(e) {
            e.preventDefault();
            updateImage(index);
            renderPagination(index);
            updateURL(index + 1);
        });
    });

    // Zoom functionality
    zoomInBtn.addEventListener('click', () => {
        zoomLevel = Math.min(zoomLevel + 0.2, 2);
        updateZoom();
    });
    zoomOutBtn.addEventListener('click', () => {
        zoomLevel = Math.max(zoomLevel - 0.2, 0.5);
        updateZoom();
    });

    // New: Zoom panning (left-click drag only when zoomed)
    imageContainer.addEventListener('mousedown', (e) => {
        if (zoomLevel <= 1 || cropper || e.button !== 0) return; // Only left-click, when zoomed, and not cropping
        isDragging = true;
        startX = e.clientX - translateX;
        startY = e.clientY - translateY;
        imageContainer.style.cursor = 'grab';
    });

    imageContainer.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        e.preventDefault(); // Prevent text selection
        translateX = e.clientX - startX;
        translateY = e.clientY - startY;
        updateZoom();
    });

    imageContainer.addEventListener('mouseup', () => {
        isDragging = false;
        imageContainer.style.cursor = 'pointer';
    });

    imageContainer.addEventListener('mouseleave', () => {
        isDragging = false;
        imageContainer.style.cursor = 'pointer';
    });

    // Touch swipe navigation (unchanged)
    let touchStartX = 0;
    let touchStartY = 0;
    let touchMoveX = 0;
    let touchMoveY = 0;
    
    imageContainer.addEventListener('touchstart', e => {
        touchStartX = e.touches[0].clientX;
        touchStartY = e.touches[0].clientY;
    });
    
    imageContainer.addEventListener('touchmove', e => {
        if (!e.touches || !e.touches[0]) return;
        touchMoveX = e.touches[0].clientX;
        touchMoveY = e.touches[0].clientY;
        const deltaX = touchStartX - touchMoveX;
        const deltaY = touchStartY - touchMoveY;
        
        // Only prevent default (block scrolling) if swipe is predominantly horizontal
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 10) {
            e.preventDefault(); // Prevent scrolling only for horizontal swipes
        }
    }, { passive: false });

    imageContainer.addEventListener('touchend', e => {
        if (!e.changedTouches || !e.changedTouches[0]) return;
        const touchEndX = e.changedTouches[0].clientX;
        const touchEndY = e.changedTouches[0].clientY;
        const swipeDistanceX = touchStartX - touchEndX;
        const swipeDistanceY = touchStartY - touchEndY;

        // Trigger navigation only if swipe is predominantly horizontal and exceeds threshold
        if (Math.abs(swipeDistanceX) > Math.abs(swipeDistanceY) && Math.abs(swipeDistanceX) > 50) {
            if (swipeDistanceX > 0) navigateImage(1); // Swipe left
            else navigateImage(-1); // Swipe right
        }
    });

    // Full-screen functionality
    fullScreenBtn.addEventListener('click', () => {
        const viewer = document.querySelector('.main-viewer');
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            viewer.requestFullscreen().catch(err => console.error('Full screen failed:', err));
        }
    });

    // Navigation buttons
    prevBtn.addEventListener('click', () => navigateImage(-1));
    nextBtn.addEventListener('click', () => navigateImage(1));
    imagePrevBtn?.addEventListener('click', () => navigateImage(-1));
    imageNextBtn?.addEventListener('click', () => navigateImage(1));

    // Pagination click handler
    pagination.addEventListener('click', function(e) {
        const link = e.target.closest('.page-link');
        if (!link) return;
        e.preventDefault();
        const page = parseInt(link.getAttribute('data-page'));
        if (!isNaN(page)) {
            const newIndex = page - 1;
            updateImage(newIndex);
            renderPagination(newIndex);
            updateURL(page);
        }
    });

    // Social share buttons (main viewer)
    document.querySelectorAll('.social-share-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const platform = this.getAttribute('data-platform');
            shareOnPlatform(platform, currentUrl);
        });
    });

    // Popup trigger for image click
    images.forEach(image => {
        image.addEventListener('click', function() {
            const imagePath = this.getAttribute('src');
            const popupUrl = `${baseUrl}/popup_template.php?image=${encodeURIComponent(imagePath)}`;
            popupImage.src = imagePath;
            document.querySelectorAll('#imagePopup .social-share-btn').forEach(btn => {
                btn.setAttribute('data-url', popupUrl);
            });
            popupModal.show();
        });
    });

    // Social share buttons in image popup
    document.querySelectorAll('#imagePopup .social-share-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const platform = this.getAttribute('data-platform');
            const url = this.getAttribute('data-url');
            shareOnPlatform(platform, url);
        });
    });

    // PDF Download functionality
    pdfDownloadBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        generatePDF();
    });

    // Clip button functionality
    const clipButton = document.querySelector('.clip-button');
    clipButton?.addEventListener('click', () => {
        const currentImage = document.querySelector('.full-image.active');
        if (!currentImage) return;

        if (cropper) {
            cropper.destroy();
            cropper = null;
            document.querySelector('.zoom-in').disabled = false;
            document.querySelector('.zoom-out').disabled = false;
            clipButton.innerHTML = '<i class="fa-solid fa-scissors"></i> Clip';
            return;
        }

        cropper = new Cropper(currentImage, {
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
                const imgWidth = currentImage.naturalWidth;
                const imgHeight = currentImage.naturalHeight;
                const cropWidth = imgWidth * 0.3;
                const cropHeight = imgHeight * 0.3;
                const x = (imgWidth - cropWidth) / 2;
                const y = (imgHeight - cropHeight) / 4.5;

                cropper.setData({
                    x: x,
                    y: y,
                    width: cropWidth,
                    height: cropHeight
                });

                const cropBox = document.querySelector('.cropper-crop-box');
                const shareBtn = document.createElement('button');
                shareBtn.className = 'cropper-share-btn';
                shareBtn.innerHTML = '<i class="fas fa-share-alt"></i> Share It';
                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'cropper-cancel-btn';
                cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';

                cropBox.appendChild(shareBtn);
                cropBox.appendChild(cancelBtn);

                updateButtonPositions(shareBtn, cancelBtn);

                shareBtn.addEventListener('click', () => {
                    const canvas = cropper.getCroppedCanvas();
                    if (canvas) {
                        canvas.toBlob(blob => {
                            const formData = new FormData();
                            formData.append('image', blob, 'cropped.jpg');
                            formData.append('edition_id', editionId);
                            formData.append('image_id', imageIds[currentIndex]);

                            $.ajax({
                                url: 'save_clip.php',
                                type: 'POST',
                                data: formData,
                                processData: false,
                                contentType: false,
                                success: function(response) {
                                    const data = JSON.parse(response);
                                    if (data.success) {
                                        const clipUrl = `${baseUrl}/clips.php?id=${data.clip_id}`;
                                        document.getElementById('clipPreviewImage').src = data.clip_path;
                                        document.getElementById('clipPreviewLink').value = clipUrl;
                                        document.querySelectorAll('#clipPreviewModal .social-share-btn').forEach(btn => {
                                            btn.setAttribute('data-url', clipUrl);
                                        });
                                        clipPreviewModal.show();
                                        cropper.destroy();
                                        cropper = null;
                                        document.querySelector('.zoom-in').disabled = false;
                                        document.querySelector('.zoom-out').disabled = false;
                                        clipButton.innerHTML = '<i class="fa-solid fa-scissors"></i> Clip';
                                    } else {
                                        alert('Failed to save clip: ' + data.message);
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.error('AJAX error:', error);
                                    alert('Error saving clip. Please try again.');
                                }
                            });
                        }, 'image/jpeg');
                    }
                });

                cancelBtn.addEventListener('click', () => {
                    cropper.destroy();
                    cropper = null;
                    document.querySelector('.zoom-in').disabled = false;
                    document.querySelector('.zoom-out').disabled = false;
                    clipButton.innerHTML = '<i class="fa-solid fa-scissors"></i> Clip';
                });

                cropper.on('cropmove', () => updateButtonPositions(shareBtn, cancelBtn));
                cropper.on('cropend', () => updateButtonPositions(shareBtn, cancelBtn));
            }
        });

        document.querySelector('.zoom-in').disabled = true;
        document.querySelector('.zoom-out').disabled = true;
        clipButton.innerHTML = '<i class="fa-solid fa-scissors"></i> Stop';
    });

    // Datepicker for Archive
    const datepickerContainer = $("#datepicker-container");
    $("#archive-button").click(function() {
        datepickerContainer.toggle();
        const isMobile = window.matchMedia("(max-width: 768px)").matches;
        
        if (datepickerContainer.is(':visible')) {
            if (isMobile) {
                // Mobile/tablet: Modal overlay
                datepickerContainer.css({
                    position: 'fixed',
                    top: '50%',
                    left: '50%',
                    transform: 'translate(-50%, -50%)',
                    zIndex: 1050,
                    backgroundColor: '#fff',
                    padding: '10px',
                    boxShadow: '0 4px 8px rgba(0,0,0,0.2)',
                    borderRadius: '4px'
                }).datepicker({
                    beforeShowDay: function(date) {
                        const formattedDate = $.datepicker.formatDate('yy-mm-dd', date);
                        return [availableDates.includes(formattedDate), ''];
                    },
                    onSelect: function(dateText) {
                        const editionId = editionMap[dateText];
                        if (editionId) {
                            window.location.href = 'index.php?id=' + editionId;
                        }
                        datepickerContainer.hide();
                        removeOverlay();
                    },
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });

                // Add overlay for mobile
                const overlay = document.createElement('div');
                overlay.className = 'datepicker-overlay';
                overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040;';
                document.body.appendChild(overlay);
                overlay.addEventListener('click', () => {
                    datepickerContainer.hide();
                    removeOverlay();
                });
            } else {
                // Desktop: Original absolute positioning
                datepickerContainer.css({
                    position: 'absolute',
                    top: 'calc(100% + 5px)',
                    right: 0,
                    transform: 'translateX(0)',
                    zIndex: 1000,
                    backgroundColor: '#fff',
                    border: '1px solid #ddd',
                    borderRadius: '2px',
                    boxShadow: '0 2px 5px rgba(0,0,0,0.1)',
                    padding: '5px'
                }).datepicker({
                    beforeShowDay: function(date) {
                        const formattedDate = $.datepicker.formatDate('yy-mm-dd', date);
                        return [availableDates.includes(formattedDate), ''];
                    },
                    onSelect: function(dateText) {
                        const editionId = editionMap[dateText];
                        if (editionId) {
                            window.location.href = 'index.php?id=' + editionId;
                        }
                        datepickerContainer.hide();
                    },
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true
                });
            }
        } else {
            removeOverlay();
        }
    });

    // Helper function to remove overlay
    function removeOverlay() {
        const overlay = document.querySelector('.datepicker-overlay');
        if (overlay) overlay.remove();
    }

    // Other helper functions
    function updateButtonPositions(shareBtn, cancelBtn) {
        shareBtn.style.position = 'absolute';
        shareBtn.style.top = '-25px';
        shareBtn.style.left = '0px';
        shareBtn.style.zIndex = '1002';

        cancelBtn.style.position = 'absolute';
        cancelBtn.style.top = '-25px';
        cancelBtn.style.left = '85px';
        cancelBtn.style.zIndex = '1002';
    }

    function shareOnPlatform(platform, url) {
        switch (platform) {
            case 'facebook': window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`, '_blank'); break;
            case 'twitter': window.open(`https://x.com/intent/tweet?url=${encodeURIComponent(url)}`, '_blank'); break;
            case 'whatsapp': window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(url)}`, '_blank'); break;
            case 'linkedin': window.open(`https://www.linkedin.com/shareArticle?mini=true&url=${encodeURIComponent(url)}`, '_blank'); break;
            case 'telegram': window.open(`https://t.me/share/url?url=${encodeURIComponent(url)}`, '_blank'); break;
            case 'print': window.print(); break;
            case 'email': window.location.href = `mailto:?subject=Check this clip&body=${encodeURIComponent(url)}`; break;
        }
    }

    document.getElementById('copyClipLinkBtn')?.addEventListener('click', function() {
        const linkInput = document.getElementById('clipPreviewLink');
        linkInput.select();
        navigator.clipboard.writeText(linkInput.value).then(() => {
            alert('Link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy link. Please copy manually.');
        });
    });

    document.querySelectorAll('#clipPreviewModal .social-share-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const platform = this.getAttribute('data-platform');
            const url = this.getAttribute('data-url');
            shareOnPlatform(platform, url);
        });
    });

    function updateImage(index) {
        if (index < 0 || index >= totalPages) return;
        images.forEach(img => img.classList.remove('active'));
        images[index].classList.add('active');
        thumbnails.forEach(thumb => thumb.classList.remove('active'));
        thumbnails[index].classList.add('active');
        currentIndex = index;
        pageCounter.textContent = `Page ${currentIndex + 1} of ${totalPages}`;
        resetZoom();
    }

    function navigateImage(direction) {
        const newIndex = currentIndex + direction;
        if (newIndex < 0 || newIndex >= totalPages) return;
        updateImage(newIndex);
        renderPagination(newIndex);
        updateURL(newIndex + 1);
    }

    function renderPagination(index) {
        const currentPage = index + 1;
        const isMobile = window.matchMedia("(max-width: 576px)").matches;
        let html = '';
        if (isMobile) {
            html = `
                <a href="#" class="page-link prev-page" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a>
                <span class="page-link active">${currentPage}</span>
                <a href="#" class="page-link next-page" data-page="${currentPage + 1}"><i class="fas fa-chevron-right"></i></a>
            `;
        } else {
            const startPage = Math.floor((currentPage - 1) / pagesPerGroup) * pagesPerGroup + 1;
            const endPage = Math.min(startPage + pagesPerGroup - 1, totalPages);
            
            if (currentPage > 1) {
                html += `<a href="#" class="page-link prev-page" data-page="${currentPage - 1}"><i class="fas fa-chevron-left"></i></a>`;
            }
            for (let i = startPage; i <= endPage; i++) {
                const activeClass = (i === currentPage) ? 'active' : '';
                html += `<a href="#" class="page-link ${activeClass}" data-page="${i}">${i}</a>`;
            }
            if (currentPage < totalPages) {
                html += `<a href="#" class="page-link next-page" data-page="${currentPage + 1}"><i class="fas fa-chevron-right"></i></a>`;
            }
        }
        pagination.innerHTML = html;
    }

    function updateZoom() {
        if (cropper) return;
        const image = images[currentIndex];
        const containerWidth = imageContainer.clientWidth;
        const containerHeight = imageContainer.clientHeight;
        const imageWidth = image.naturalWidth * zoomLevel;
        const imageHeight = image.naturalHeight * zoomLevel;

        // Limit translation to keep image within bounds
        translateX = Math.max(-(imageWidth - containerWidth), Math.min(0, translateX));
        translateY = Math.max(-(imageHeight - containerHeight), Math.min(0, translateY));

        image.style.transform = `scale(${zoomLevel}) translate(${translateX}px, ${translateY}px)`;
        image.style.transformOrigin = 'center center';
    }

    function resetZoom() {
        zoomLevel = 1;
        translateX = 0;
        translateY = 0;
        updateZoom();
    }

    function updateURL(page) {
        const url = new URL(window.location);
        url.searchParams.set('id', editionId);
        url.searchParams.set('page', page);
        window.history.pushState({}, '', url);
    }

    function showPDFLoadingAnimation(current = 0, total = totalPages) {
        pdfDownloadBtn.disabled = true;
        pdfDownloadBtn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Generating ${current}/${total}`;
    }

    function hidePDFLoadingAnimation() {
        pdfDownloadBtn.disabled = false;
        pdfDownloadBtn.innerHTML = '<i class="fas fa-download"></i> PDF';
    }

    async function generatePDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('p', 'mm', 'a4');
        const pageWidth = 210;
        const pageHeight = 297;

        showPDFLoadingAnimation(0);
        for (let i = 0; i < imagePaths.length; i++) {
            const img = new Image();
            img.crossOrigin = 'Anonymous';
            img.src = imagePaths[i];
            await new Promise(resolve => img.onload = resolve);
            const imgWidth = img.width;
            const imgHeight = img.height;
            const scale = Math.min(pageWidth / imgWidth, pageHeight / imgHeight);
            const scaledWidth = imgWidth * scale;
            const scaledHeight = imgHeight * scale;
            if (i > 0) doc.addPage();
            doc.addImage(img, 'JPEG', (pageWidth - scaledWidth) / 2, (pageHeight - scaledHeight) / 2, scaledWidth, scaledHeight);
            showPDFLoadingAnimation(i + 1); // Update progress
        }
        doc.save(`${editionTitle.replace(/\s+/g, '-')}-edition.pdf`);
        hidePDFLoadingAnimation();
    }

    // Floating social share button functionality
    const floatingShareBtn = document.createElement('button');
    floatingShareBtn.className = 'floating-share-btn';
    floatingShareBtn.innerHTML = '<i class="fas fa-share"></i>';
    document.body.appendChild(floatingShareBtn);

    const sharePopup = document.createElement('div');
    sharePopup.id = 'sharePopup';
    sharePopup.innerHTML = `
        <button class="close-share-btn"><i class="fas fa-times"></i></button>
        <a href="#" class="social-share-btn" data-platform="facebook"><i class="fab fa-facebook"></i></a>
        <a href="#" class="social-share-btn" data-platform="twitter"><i class="fab fa-twitter"></i></a>
        <a href="#" class="social-share-btn" data-platform="whatsapp"><i class="fab fa-whatsapp"></i></a>
        <a href="#" class="social-share-btn" data-platform="linkedin"><i class="fab fa-linkedin"></i></a>
        <a href="#" class="social-share-btn" data-platform="telegram"><i class="fab fa-telegram"></i></a>
        <a href="#" class="social-share-btn" data-platform="print"><i class="fas fa-print"></i></a>
        <a href="#" class="social-share-btn" data-platform="email"><i class="fas fa-envelope"></i></a>
    `;
    document.body.appendChild(sharePopup);

    floatingShareBtn.addEventListener('click', function(e) {
        e.preventDefault();
        sharePopup.style.display = sharePopup.style.display === 'block' ? 'none' : 'block';
    });

    // Close button for share popup
    const closeShareBtn = sharePopup.querySelector('.close-share-btn');
    if (closeShareBtn) {
        closeShareBtn.addEventListener('click', function(e) {
            e.preventDefault();
            sharePopup.style.display = 'none';
        });
    }

    // Social share functionality for floating button
    document.querySelectorAll('#sharePopup .social-share-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const platform = this.getAttribute('data-platform');
            shareOnPlatform(platform, currentUrl);
            sharePopup.style.display = 'none'; // Close after sharing
        });
    });

    // Close popup if clicking outside
    document.addEventListener('click', function(e) {
        if (!floatingShareBtn.contains(e.target) && !sharePopup.contains(e.target)) {
            sharePopup.style.display = 'none';
        }
    });
});
