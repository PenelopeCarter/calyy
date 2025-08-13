
document.getElementById('uploadForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent form submission from reloading the page

    // Get the values from the form
    const title = document.getElementById('titleInput').value;
    const description = document.getElementById('descriptionInput').value;
    const link = document.getElementById('linkInput').value;
    const image = document.getElementById('imageInput').files[0];

    // Create a FileReader to read the image
    const reader = new FileReader();
    reader.onload = function(e) {
        // Create new gallery element
        const newGallery = document.createElement('div');
        newGallery.classList.add('gallery');

        // Image element
        const img = document.createElement('img');
        img.src = e.target.result;
        img.alt = title;

        // Title element
        const titleElement = document.createElement('div');
        titleElement.classList.add('title');
        titleElement.textContent = title;

        // Description element
        const descElement = document.createElement('div');
        descElement.classList.add('description');
        descElement.innerHTML = `<p>${description}</p><a href="${link}" target="_blank">Download Link</a>`;

        // Append elements to new gallery
        newGallery.appendChild(img);
        newGallery.appendChild(titleElement);
        newGallery.appendChild(descElement);

        // Append new gallery to the container
        document.getElementById('galleryContainer').appendChild(newGallery);

        // Clear the form after submission
        document.getElementById('uploadForm').reset();
    };

    // Read the image as a data URL
    if (image) {
        reader.readAsDataURL(image);
    }
});
