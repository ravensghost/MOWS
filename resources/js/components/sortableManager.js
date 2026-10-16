export default (endpoint) => ({
    init() {
        new window.Sortable(this.$el, {
            animation: 150,
            onEnd: async () => {
                const items = Array.from(this.$el.querySelectorAll(':scope > [data-id]')).map((item, index) => ({
                    id: item.dataset.id,
                    sort_order: index + 1
                }));

                try {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ items: items })
                    });
                    if (!response.ok) {
                        window.Swal.fire('Error', 'Failed to update order.', 'error');
                    }
                } catch (err) {
                    window.Swal.fire('Error', 'Network error occurred during reordering.', 'error');
                }
            }
        });
    }
});