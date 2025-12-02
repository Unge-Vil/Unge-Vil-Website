(() => {
    const getExperienceYear = (post) => {
        const metaDates = (post?.meta?.uv_experience_dates ?? '').toString();
        const matchedYear = metaDates.match(/\b(\d{4})\b/);

        if (matchedYear?.[1]) {
            return matchedYear[1];
        }

        if (post?.date) {
            const publishedDate = new Date(post.date);

            if (!Number.isNaN(publishedDate.getFullYear())) {
                return String(publishedDate.getFullYear());
            }
        }

        return '';
    };

    const getGroupListClasses = (layout) => {
        const classes = ['uv-experiences__year-list'];

        if (layout !== 'list') {
            classes.push('uv-card-list');
        }

        if (layout === 'grid') {
            classes.push('uv-card-grid', 'columns-3');
        }

        return classes.join(' ');
    };

    const createCard = (post, year) => {
        const metaOrg = post?.meta?.uv_experience_org;
        const metaDates = post?.meta?.uv_experience_dates;
        const thumbnail = post?._embedded?.['wp:featuredmedia']?.[0]?.source_url;

        const item = document.createElement('li');
        item.className = 'uv-card uv-card--experience';

        const anchor = document.createElement('a');
        anchor.href = post?.link ?? '#';
        anchor.rel = 'bookmark';

        if (thumbnail) {
            const img = document.createElement('img');
            img.src = thumbnail;
            img.alt = post?.title?.rendered ?? '';
            anchor.appendChild(img);
        } else {
            const icon = document.createElement('div');
            icon.className = 'uv-card-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML =
                '<svg viewBox="0 0 24 24" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.75a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M5.25 19.5a6.75 6.75 0 0 1 13.5 0" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
            anchor.appendChild(icon);
        }

        const body = document.createElement('div');
        body.className = 'uv-card-body';

        const yearEl = document.createElement('div');
        yearEl.className = 'uv-card-meta__year';
        yearEl.textContent = year;
        body.appendChild(yearEl);

        const heading = document.createElement('h3');
        heading.textContent = post?.title?.rendered ?? '';
        body.appendChild(heading);

        if (metaOrg || metaDates) {
            const metaWrapper = document.createElement('div');
            metaWrapper.className = 'uv-card-meta';

            if (metaOrg) {
                const orgEl = document.createElement('div');
                orgEl.className = 'uv-card-meta__org';
                orgEl.textContent = metaOrg;
                metaWrapper.appendChild(orgEl);
            }

            if (metaDates) {
                const datesEl = document.createElement('div');
                datesEl.className = 'uv-card-meta__dates';
                datesEl.textContent = metaDates;
                metaWrapper.appendChild(datesEl);
            }

            body.appendChild(metaWrapper);
        }

        if (post?.excerpt?.rendered) {
            const excerpt = document.createElement('div');
            excerpt.className = 'uv-card-excerpt';
            excerpt.innerHTML = post.excerpt.rendered;
            body.appendChild(excerpt);
        }

        anchor.appendChild(body);
        item.appendChild(anchor);

        return item;
    };

    const ensureYearList = (experiencesList, year, layout) => {
        const existingGroup = experiencesList.querySelector(
            `.uv-experiences__year-group[data-year="${year}"]`,
        );

        if (existingGroup) {
            return existingGroup.querySelector('.uv-experiences__year-list');
        }

        const group = document.createElement('li');
        group.className = 'uv-experiences__year-group';
        group.dataset.year = year;

        const heading = document.createElement('div');
        heading.className = 'uv-experiences__year-heading';
        heading.textContent = year;

        const list = document.createElement('ul');
        list.className = getGroupListClasses(layout);

        group.appendChild(heading);
        group.appendChild(list);

        const groups = Array.from(
            experiencesList.querySelectorAll('.uv-experiences__year-group'),
        );
        const insertBefore = groups.find((groupEl) => {
            const groupYear = groupEl.getAttribute('data-year') || '';

            return groupYear.localeCompare(year) < 0;
        });

        if (insertBefore) {
            experiencesList.insertBefore(group, insertBefore);
        } else {
            experiencesList.appendChild(group);
        }

        return list;
    };

    const appendPosts = (experiencesList, posts, layout) => {
        posts.forEach((post) => {
            const year = getExperienceYear(post) || '';
            const yearList = ensureYearList(experiencesList, year, layout);

            yearList.appendChild(createCard(post, year));
        });
    };

    const setupPagination = (block) => {
        const paginationEnabled = block.dataset.pagination === '1';
        const loadMoreButton = block.querySelector('[data-action="load-more"]');
        const experiencesList = block.querySelector('.uv-experiences');

        if (!paginationEnabled || !loadMoreButton || !experiencesList) {
            return;
        }

        let page = parseInt(block.dataset.page ?? '1', 10) || 1;
        let totalPages = parseInt(block.dataset.totalPages ?? '1', 10) || 1;
        const count = parseInt(block.dataset.count ?? '3', 10) || 3;
        const year = block.dataset.year ?? '';
        const layout = block.dataset.layout ?? 'grid';
        const restUrl = block.dataset.restUrl || '/wp-json/wp/v2/uv_experience';

        const loadMoreText = block.dataset.loadMoreText || 'Last inn flere';
        const loadedText = block.dataset.loadedText || 'Alle erfaringer er lastet inn';
        const loadingText = block.dataset.loadingText || 'Laster…';
        const errorText = block.dataset.errorText || 'Kunne ikke laste flere erfaringer.';

        let isLoading = false;
        let lastError = '';

        const updateButtonState = () => {
            if (lastError) {
                loadMoreButton.disabled = false;
                loadMoreButton.textContent = lastError;
                loadMoreButton.removeAttribute('aria-disabled');
                return;
            }

            const hasMore = page < totalPages;

            if (!hasMore) {
                loadMoreButton.disabled = true;
                loadMoreButton.textContent = loadedText;
                loadMoreButton.setAttribute('aria-disabled', 'true');
                return;
            }

            loadMoreButton.disabled = isLoading;
            loadMoreButton.textContent = isLoading ? loadingText : loadMoreText;
            loadMoreButton.removeAttribute('aria-disabled');
        };

        updateButtonState();

        loadMoreButton.addEventListener('click', async () => {
            if (isLoading || page >= totalPages) {
                return;
            }

            const nextPage = page + 1;
            const requestUrl = new URL(restUrl, window.location.origin);
            requestUrl.searchParams.set('per_page', count);
            requestUrl.searchParams.set('page', nextPage);
            requestUrl.searchParams.set('_embed', '1');
            requestUrl.searchParams.set(
                '_fields',
                'id,title,excerpt,link,meta,featured_media,date,_links.wp:featuredmedia',
            );

            if (year) {
                requestUrl.searchParams.set('after', `${year}-01-01T00:00:00`);
                requestUrl.searchParams.set('before', `${year}-12-31T23:59:59`);
            }

            try {
                lastError = '';
                isLoading = true;
                updateButtonState();

                const response = await fetch(requestUrl.toString());

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const responseTotalPages = parseInt(
                    response.headers.get('X-WP-TotalPages') || '',
                    10,
                );

                if (responseTotalPages) {
                    totalPages = responseTotalPages;
                    block.dataset.totalPages = String(totalPages);
                }

                const posts = await response.json();
                appendPosts(experiencesList, posts, layout);

                page = nextPage;
                block.dataset.page = String(page);
            } catch (error) {
                lastError = errorText;
            } finally {
                isLoading = false;
                updateButtonState();
            }
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const blocks = document.querySelectorAll(
            '.wp-block-uv-experiences[data-pagination="1"]',
        );

        blocks.forEach(setupPagination);
    });
})();
