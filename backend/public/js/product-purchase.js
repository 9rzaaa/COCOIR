// All purchase forms share refreshed CSRF tokens, including editorial product cards.
document.querySelectorAll('.product-purchase').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const feedback = form.querySelector('.purchase-feedback');
        button.disabled = true;
        button.textContent = 'Adding…';
        feedback.textContent = '';
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const result = await response.json();
            if (result.csrf_hash) {
                document.querySelectorAll('.product-purchase input[data-csrf]').forEach(input => { input.value = result.csrf_hash; });
            }
            if (!response.ok || !result.success) throw new Error(result.message || 'Could not add this product. Please try again.');
            feedback.textContent = 'Added to your cart.';
            window.dispatchEvent(new CustomEvent('cartUpdated', { detail: { count: result.totalItems } }));
        } catch (error) {
            feedback.textContent = error.message === 'Failed to fetch' ? 'Connection failed. Please try again.' : error.message;
        } finally {
            button.disabled = false;
            button.textContent = 'Add to cart';
        }
    });
    // Identify the framework-generated CSRF field without hardcoding its configured name.
    const csrf = form.querySelector('input[type="hidden"]');
    if (csrf) csrf.dataset.csrf = 'true';
});
