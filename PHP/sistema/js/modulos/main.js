window.VendingBox = {
    Cache: {
        products: null,
        categories: null,
        async init() {
            const cachedProducts = localStorage.getItem('cached_products');
            const cachedCategories = localStorage.getItem('cached_categories');
            
            if (cachedProducts && cachedCategories) {
                this.products = JSON.parse(cachedProducts);
                this.categories = JSON.parse(cachedCategories);
                return;
            }
            await this.refresh();
        },

        async refresh() {
            try {
                const [productsResponse, categoriesResponse] = await Promise.all([
                    fetch('get_products.php'),
                    fetch('get_categories.php')
                ]);

                this.products = await productsResponse.json();
                this.categories = await categoriesResponse.json();

                localStorage.setItem('cached_products', JSON.stringify(this.products));
                localStorage.setItem('cached_categories', JSON.stringify(this.categories));
            } catch (error) {
                console.error('Error al actualizar caché:', error);
            }
        }
    },

    Products: {
        async load() {
            try {
                if (!VendingBox.Cache.products) {
                    await VendingBox.Cache.init();
                }
            } catch (error) {
                console.error('Error al cargar productos:', error);
            }
        },
        async filterByCategory(categoryId) {
            try {
                const url = categoryId === '0' 
                    ? 'get_products.php'
                    : `get_products.php?categoria=${categoryId}`;
                    
                const response = await fetch(url);
                const productos = await response.json();
            } catch (error) {
                console.error('Error al filtrar productos:', error);
            }
        }
    },

    Categories: {
        async load() {
            try {
                const response = await fetch('get_categories.php');
                const categorias = await response.json();
                this.display(categorias);
                this.displayHeader(categorias);
            } catch (error) {
                console.error('Error al cargar categorías:', error);
            }
        },

        display(categorias) {
            const categoriesBar = document.getElementById('categories-bar');
            if (!categoriesBar) return;

            categoriesBar.innerHTML = `
                <button class="category-btn active" data-category-id="0">Todas</button>
                ${categorias.map(cat => `
                    <button class="category-btn" data-category-id="${cat.id_categoria}">
                        ${cat.nombre_categoria}
                    </button>
                `).join('')}
            `;

            categoriesBar.addEventListener('click', async (e) => {
                if (e.target.classList.contains('category-btn')) {
                    const categoryId = e.target.dataset.categoryId;
                    document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
                    e.target.classList.add('active');
                    await VendingBox.Products.filterByCategory(categoryId);
                }
            });
        },

        displayHeader(categorias) {
            const headerBar = document.getElementById('header-categories-bar');
            if (!headerBar) return;

            headerBar.innerHTML = `
                <button class="header-category-btn active" id="btn-all-categories">Categorías</button>
                <button class="header-category-btn" id="btn-only-subcategories">Subcategorías</button>
                <button class="header-category-btn" id="btn-cats-and-subs">Cat + Subcat</button>
                <button class="header-category-btn" id="btn-all-products">Productos</button>
            `;

            const setActive = (btn) => {
                headerBar.querySelectorAll('.header-category-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            };

            const btnAllCategories = document.getElementById('btn-all-categories');
            const btnOnlySubcategories = document.getElementById('btn-only-subcategories');
            const btnCatsAndSubs = document.getElementById('btn-cats-and-subs');
            const btnAllProducts = document.getElementById('btn-all-products');

            btnAllCategories.addEventListener('click', () => {
                setActive(btnAllCategories);
                document.dispatchEvent(new CustomEvent('headerFilter', { detail: { mode: 'categories' } }));
            });
            btnOnlySubcategories.addEventListener('click', () => {
                setActive(btnOnlySubcategories);
                document.dispatchEvent(new CustomEvent('headerFilter', { detail: { mode: 'subcategories' } }));
            });
            btnCatsAndSubs.addEventListener('click', () => {
                setActive(btnCatsAndSubs);
                document.dispatchEvent(new CustomEvent('headerFilter', { detail: { mode: 'cats_and_subs' } }));
            });
            btnAllProducts.addEventListener('click', () => {
                setActive(btnAllProducts);
                document.dispatchEvent(new CustomEvent('headerFilter', { detail: { mode: 'products' } }));
            });
        }
    },

    Services: {
        init() {
        }
    },

    init() {
        this.Categories.load();
        this.Services.init();
    }
};

document.addEventListener('DOMContentLoaded', () => VendingBox.init());
