function updateSingleImages() {
    const lbSingles = document.querySelectorAll('a.lb-single');
    for (let i = 0; i < lbSingles.length; i++) {
        lbSingles[i].setAttribute('data-fslightbox', '_eb_lb_single' + '_' + i);
    }
}

function updateGroupImages() {
    const lbGroups = document.querySelectorAll('.lb-group');
    for (let i = 0; i < lbGroups.length; i++) {
        let groupImages = lbGroups[i].querySelectorAll("a");
        for (let j = 0; j < groupImages.length; j++) {
            groupImages[j].setAttribute('data-fslightbox', '_eb_lb_group' + '_' + i);
        }
    }
}

updateSingleImages();
updateGroupImages();
refreshFsLightbox();
