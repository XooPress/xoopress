/**
 * Admin Marketplace Details Modal
 * 
 * Fetches enhanced module/theme details from the API and displays
 * them in a modal with changelog, requirements, screenshots, etc.
 */
(function () {
    'use strict';

    // ── Modal HTML Template ──────────────────────────────────

    var MODAL_HTML = '' +
        '<div id="marketplace-detail-modal" class="mp-modal-overlay" style="display:none;">' +
        '  <div class="mp-modal-container">' +
        '    <div class="mp-modal-header">' +
        '      <h3 id="mp-modal-title" class="mp-modal-title"></h3>' +
        '      <button class="mp-modal-close" onclick="closeMarketplaceDetail()">&times;</button>' +
        '    </div>' +
        '    <div class="mp-modal-body">' +
        '      <div id="mp-modal-loading" style="text-align:center;padding:40px;">Loading details...</div>' +
        '      <div id="mp-modal-content" style="display:none;">' +
        '        <div class="mp-detail-section">' +
        '          <div class="mp-description" id="mp-description"></div>' +
        '          <div class="mp-meta-grid" id="mp-meta"></div>' +
        '          <div class="mp-status-row" id="mp-status-row"></div>' +
        '        </div>' +
        '        <div class="mp-detail-section" id="mp-requirements-section" style="display:none;">' +
        '          <h4 class="mp-section-title">Requirements</h4>' +
        '          <div id="mp-requirements"></div>' +
        '        </div>' +
        '        <div class="mp-detail-section" id="mp-screenshots-section" style="display:none;">' +
        '          <h4 class="mp-section-title">Screenshots</h4>' +
        '          <div class="mp-screenshots-grid" id="mp-screenshots"></div>' +
        '        </div>' +
        '        <div class="mp-detail-section" id="mp-changelog-section" style="display:none;">' +
        '          <h4 class="mp-section-title">Changelog</h4>' +
        '          <div id="mp-changelog"></div>' +
        '        </div>' +
        '        <div class="mp-detail-section" id="mp-upgrade-notice-section" style="display:none;">' +
        '          <div id="mp-upgrade-notice"></div>' +
        '        </div>' +
        '      </div>' +
        '    </div>' +
        '    <div class="mp-modal-footer">' +
        '      <button class="btn btn-sm btn-secondary" onclick="closeMarketplaceDetail()">Close</button>' +
        '      <a id="mp-modal-install-btn" class="btn btn-sm btn-success" href="#">Install</a>' +
        '    </div>' +
        '  </div>' +
        '</div>';

    // ── Inject Modal into DOM ───────────────────────────────

    if (document.getElementById('marketplace-detail-modal') === null) {
        var modalContainer = document.createElement('div');
        modalContainer.innerHTML = MODAL_HTML;
        document.body.appendChild(modalContainer.firstElementChild);
    }

    // ── Global Functions ─────────────────────────────────────

    /**
     * Open the marketplace detail modal for a given item
     * @param {string} type - 'module' or 'theme'
     * @param {string} slug - item slug
     */
    window.openMarketplaceDetail = function (type, slug) {
        var modal = document.getElementById('marketplace-detail-modal');
        var loading = document.getElementById('mp-modal-loading');
        var content = document.getElementById('mp-modal-content');
        var title = document.getElementById('mp-modal-title');
        var installBtn = document.getElementById('mp-modal-install-btn');

        modal.style.display = 'flex';
        loading.style.display = 'block';
        content.style.display = 'none';
        title.textContent = 'Loading...';

        var url = '/admin/marketplace/details/' + encodeURIComponent(type) + '/' + encodeURIComponent(slug);

        fetch(url)
            .then(function (response) {
                if (!response.ok) {
                    return response.json().then(function (err) {
                        throw new Error(err.error || 'Failed to load details');
                    });
                }
                return response.json();
            })
            .then(function (result) {
                if (!result.success || !result.data) {
                    throw new Error(result.error || 'Invalid response');
                }
                renderDetail(result.data, type, slug);
                loading.style.display = 'none';
                content.style.display = 'block';
                title.textContent = result.data.name || result.data.title || slug;
                installBtn.href = '/admin/marketplace/install/' + type + '/' + encodeURIComponent(slug);
            })
            .catch(function (err) {
                loading.innerHTML = '<div class="alert alert-warning">Error: ' + escapeHtml(err.message) + '</div>';
            });
    };

    /**
     * Close the marketplace detail modal
     */
    window.closeMarketplaceDetail = function () {
        var modal = document.getElementById('marketplace-detail-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // ── Render Functions ─────────────────────────────────────

    /**
     * Render the detail data into the modal
     */
    function renderDetail(data, type, slug) {
        var desc = document.getElementById('mp-description');
        var meta = document.getElementById('mp-meta');
        var statusRow = document.getElementById('mp-status-row');

        // Description
        desc.textContent = data.description || 'No description available.';

        // Meta info
        var metaHtml = '';
        if (data.version) {
            metaHtml += '<div class="mp-meta-item"><span class="mp-meta-label">Version:</span><span class="mp-meta-value">' + escapeHtml(data.version) + '</span></div>';
        }
        if (data.author) {
            metaHtml += '<div class="mp-meta-item"><span class="mp-meta-label">Author:</span><span class="mp-meta-value">' + escapeHtml(data.author) + '</span></div>';
        }
        if (data.category) {
            metaHtml += '<div class="mp-meta-item"><span class="mp-meta-label">Category:</span><span class="mp-meta-value">' + escapeHtml(data.category) + '</span></div>';
        }
        if (data.created_at) {
            var date = new Date(data.created_at);
            metaHtml += '<div class="mp-meta-item"><span class="mp-meta-label">Released:</span><span class="mp-meta-value">' + date.toLocaleDateString() + '</span></div>';
        }
        if (data.compatible_xoopress_version) {
            metaHtml += '<div class="mp-meta-item"><span class="mp-meta-label">Compatibility:</span><span class="mp-meta-value">✓ XooPress ' + escapeHtml(data.compatible_xoopress_version) + '</span></div>';
        }
        meta.innerHTML = metaHtml;

        // Status row
        var statusHtml = '';
        if (data.status) {
            statusHtml += '<span class="mp-status-badge mp-status-' + escapeHtml(data.status) + '">' + escapeHtml(data.status) + '</span>';
        }
        statusRow.innerHTML = statusHtml;

        // Requirements
        renderRequirements(data.requirements);

        // Screenshots
        renderScreenshots(data.screenshots);

        // Changelog
        renderChangelog(data.changelog);

        // Upgrade notice
        renderUpgradeNotice(data.upgrade_notice);
    }

    function renderRequirements(requirements) {
        var section = document.getElementById('mp-requirements-section');
        var container = document.getElementById('mp-requirements');

        if (!requirements) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        var html = '<table class="mp-requirements-table">';

        if (requirements.php_version) {
            html += '<tr><td>PHP</td><td>' + escapeHtml(requirements.php_version) + '</td></tr>';
        }
        if (requirements.xoopress_version) {
            html += '<tr><td>XooPress</td><td>' + escapeHtml(requirements.xoopress_version) + '</td></tr>';
        }
        if (requirements.memory_limit) {
            html += '<tr><td>Memory Limit</td><td>' + escapeHtml(requirements.memory_limit) + '</td></tr>';
        }
        if (requirements.extensions && requirements.extensions.length > 0) {
            html += '<tr><td>Extensions</td><td>' + requirements.extensions.map(function (ext) {
                return '<code>' + escapeHtml(ext) + '</code>';
            }).join(', ') + '</td></tr>';
        }
        if (requirements.dependencies && requirements.dependencies.length > 0) {
            html += '<tr><td>Dependencies</td><td>' + requirements.dependencies.map(function (dep) {
                return escapeHtml(dep);
            }).join(', ') + '</td></tr>';
        } else if (requirements.dependencies) {
            html += '<tr><td>Dependencies</td><td>None</td></tr>';
        }

        html += '</table>';
        container.innerHTML = html;
    }

    function renderScreenshots(screenshots) {
        var section = document.getElementById('mp-screenshots-section');
        var container = document.getElementById('mp-screenshots');

        if (!screenshots || screenshots.length === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        var html = '';
        screenshots.forEach(function (ss) {
            if (ss.url) {
                html += '<div class="mp-screenshot-item">';
                html += '  <div class="mp-screenshot-img" style="background:#f0f4ff;border-radius:6px;height:120px;display:flex;align-items:center;justify-content:center;font-size:2rem;border:1px solid #e0e0e0;">🖼️</div>';
                if (ss.caption) {
                    html += '  <div class="mp-screenshot-caption">' + escapeHtml(ss.caption) + '</div>';
                }
                html += '</div>';
            } else {
                html += '<div class="mp-screenshot-item">';
                html += '  <div class="mp-screenshot-img" style="background:#f0f4ff;border-radius:6px;height:120px;display:flex;align-items:center;justify-content:center;font-size:2rem;border:1px solid #e0e0e0;">📷</div>';
                if (ss.caption) {
                    html += '  <div class="mp-screenshot-caption">' + escapeHtml(ss.caption) + '</div>';
                }
                html += '</div>';
            }
        });
        container.innerHTML = html;
    }

    function renderChangelog(changelog) {
        var section = document.getElementById('mp-changelog-section');
        var container = document.getElementById('mp-changelog');

        if (!changelog || changelog.length === 0) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        var html = '';
        changelog.forEach(function (entry, index) {
            var toggleId = 'mp-changelog-toggle-' + index;
            html += '<div class="mp-changelog-entry">';
            html += '  <div class="mp-changelog-header" onclick="toggleChangelog(\'' + toggleId + '\')">';
            html += '    <strong>' + escapeHtml(entry.version) + '</strong>';
            html += '    <span class="mp-changelog-date">' + (entry.date ? escapeHtml(entry.date) : '') + '</span>';
            html += '    <span class="mp-changelog-toggle-icon">▶</span>';
            html += '  </div>';
            html += '  <div id="' + toggleId + '" class="mp-changelog-body" style="display:none;">';
            if (entry.notes && entry.notes.length > 0) {
                html += '  <ul>';
                entry.notes.forEach(function (note) {
                    html += '    <li>' + escapeHtml(note) + '</li>';
                });
                html += '  </ul>';
            }
            html += '  </div>';
            html += '</div>';
        });
        container.innerHTML = html;
    }

    function renderUpgradeNotice(notice) {
        var section = document.getElementById('mp-upgrade-notice-section');
        var container = document.getElementById('mp-upgrade-notice');

        if (!notice) {
            section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        container.innerHTML = '<div class="mp-upgrade-notice-inner">⚠️ ' + escapeHtml(notice) + '</div>';
    }

    // ── Utility Functions ────────────────────────────────────

    /**
     * Toggle changelog entry visibility
     */
    window.toggleChangelog = function (id) {
        var el = document.getElementById(id);
        if (el) {
            var isHidden = el.style.display === 'none';
            el.style.display = isHidden ? 'block' : 'none';
            var icon = el.previousElementSibling.querySelector('.mp-changelog-toggle-icon');
            if (icon) {
                icon.textContent = isHidden ? '▼' : '▶';
            }
        }
    };

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ── Close on overlay click & Escape key ──────────────────

    document.addEventListener('click', function (e) {
        var modal = document.getElementById('marketplace-detail-modal');
        if (modal && e.target === modal) {
            closeMarketplaceDetail();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMarketplaceDetail();
        }
    });
})();