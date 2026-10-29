<div class="product-modal-backdrop" id="add-product-modal"<?= $openAddProductModal ? '' : ' hidden' ?>>
    <section class="product-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="add-product-title">
        <header class="product-modal-header">
            <div>
                <p class="eyebrow">Inventory / New item</p>
                <h2 id="add-product-title">Add a product</h2>
                <p>Complete these details to keep catalog cards and product pages consistent.</p>
            </div>
            <button class="product-modal-close" type="button" data-close-product-modal aria-label="Close add product form"><i class="bi bi-x-lg"></i></button>
        </header>

        <?php if ($addProductErrors): ?>
            <div class="product-modal-errors" role="alert">
                <strong>Please review the form:</strong>
                <ul><?php foreach ($addProductErrors as $error): ?><li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form action="<?= app_url('pages/admin/manage_products.php') ?>" method="post" enctype="multipart/form-data" class="product-modal-form">
            <input type="hidden" name="product_form_token" value="<?= htmlspecialchars($addProductToken, ENT_QUOTES, 'UTF-8') ?>">
            <div class="product-modal-fields">
                <div class="product-modal-field"><label for="product-name">Product name</label><input id="product-name" name="name" value="<?= htmlspecialchars($addProductValues['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" maxlength="180" required></div>
                <div class="product-modal-field"><label for="product-type">Product type</label><select id="product-type" name="furniture_type" required><option value="">Choose a type</option><?php foreach (['chair' => 'Chair', 'table' => 'Table', 'sofa' => 'Sofa', 'bed' => 'Bed', 'cabinet' => 'Cabinet', 'desk' => 'Desk'] as $value => $label): ?><option value="<?= $value ?>" <?= strtolower((string)($addProductValues['furniture_type'] ?? '')) === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div class="product-modal-field"><label for="product-price">Price (PHP)</label><input id="product-price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars($addProductValues['price'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required></div>
                <div class="product-modal-field"><label for="product-stock">Available stock</label><input id="product-stock" name="stock" type="number" min="1" step="1" value="<?= htmlspecialchars($addProductValues['stock'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required><small>New products must start with at least 1 unit.</small></div>
                <div class="product-modal-field"><label for="product-color">Color</label><input id="product-color" name="color" value="<?= htmlspecialchars($addProductValues['color'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="For example: walnut, cream" required><small>Separate multiple colors with commas.</small></div>
                <div class="product-modal-field"><label for="product-size">Size or dimensions</label><input id="product-size" name="size" value="<?= htmlspecialchars($addProductValues['size'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="For example: 120 x 60 x 75 cm" required></div>
                <div class="product-modal-field"><label for="product-material">Material</label><input id="product-material" name="material" value="<?= htmlspecialchars($addProductValues['material'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="For example: solid acacia wood" required></div>
                <div class="product-modal-field"><label for="product-image">Product image</label><input id="product-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" required><small>JPG, PNG, or WEBP; up to 8 MB.</small></div>
                <div class="product-modal-field product-modal-field-wide"><label for="product-description">Product description</label><textarea id="product-description" name="description" maxlength="3000" placeholder="Describe the design, comfort, construction, and useful details." required><?= htmlspecialchars($addProductValues['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea></div>
            </div>
            <footer class="product-modal-actions">
                <button type="button" class="modal-secondary-button" data-close-product-modal>Cancel</button>
                <button type="submit" name="add_product" class="modal-submit-button"><i class="bi bi-plus-lg"></i> Add product</button>
            </footer>
        </form>
    </section>
</div>
