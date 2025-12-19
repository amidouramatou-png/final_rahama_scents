function openModal(product = null) {
    document.getElementById('productModal').classList.add('active');
    document.getElementById('modalTitle').innerHTML = product ? '<i class="fas fa-edit"></i> Edit Product' : '<i class="fas fa-plus-circle"></i> Add New Product';
    document.getElementById('formAction').value = product ? 'edit_product' : 'add_product';

    if (product) {
        document.getElementById('editId').value = product.id;
        document.getElementById('name').value = product.name;
        document.getElementById('category').value = product.category;
        document.getElementById('price').value = product.price;
        document.getElementById('stock').value = product.stock;
        document.getElementById('description').value = product.description || '';
        document.getElementById('existingImage').value = product.image || '';
        if (product.image) {
            document.getElementById('imagePreview').innerHTML = '<img src="' + product.image + '" style="max-width:150px; border-radius:8px;">';
        } else {
            document.getElementById('imagePreview').innerHTML = '';
        }
    } else {
        document.getElementById('productForm').reset();
        document.getElementById('imagePreview').innerHTML = '';
        document.getElementById('editId').value = '';
        document.getElementById('existingImage').value = '';
    }
}

function closeModal() {
    document.getElementById('productModal').classList.remove('active');
}

document.getElementById('productModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

document.getElementById('image').addEventListener('change', function(e) {
    if (e.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = '<img src="' + e.target.result + '" style="max-width:150px; border-radius:8px;">';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

function showSection(sectionId) {
    document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));

    document.getElementById(sectionId).classList.add('active');
    document.querySelector('.sidebar-menu a[href="#' + sectionId + '"]').classList.add('active');
}

document.querySelectorAll('.sidebar-menu a').forEach(link => {
    link.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href.startsWith('#')) {
            e.preventDefault();
            showSection(href.substring(1));
        }
    });
});

window.addEventListener('load', function() {
    const hash = window.location.hash.substring(1);
    if (hash && document.getElementById(hash)) {
        showSection(hash);
    }
});