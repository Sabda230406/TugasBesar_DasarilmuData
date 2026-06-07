.app-pagination {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 0.85rem;
	padding-top: 1rem;
	border-top: 1px solid rgba(148, 163, 184, 0.18);
}

.app-pagination-summary {
	color: var(--text-soft, var(--admin-muted, #64748b));
	font-size: 0.86rem;
	font-weight: 800;
}

.app-pagination nav {
	margin-left: auto;
}

.app-pagination .pagination {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: flex-end;
	gap: 0.45rem;
	margin: 0;
}

.app-pagination .page-item .page-link {
	min-width: 40px;
	height: 40px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 0 0.72rem;
	margin-left: 0;
	border: 1px solid var(--line, var(--admin-line, #d6e2ea));
	border-radius: 8px;
	background: #ffffff;
	color: var(--brand-deep, var(--admin-brand-deep, #0f5e57));
	font-weight: 800;
	line-height: 1;
	box-shadow: none;
	transition: background 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}

.app-pagination .page-item:first-child .page-link,
.app-pagination .page-item:last-child .page-link {
	border-radius: 8px;
}

.app-pagination .page-item .page-link:hover,
.app-pagination .page-item .page-link:focus {
	border-color: var(--brand-deep, var(--admin-brand-deep, #0f5e57));
	background: var(--brand-light, var(--admin-brand-soft, #dff7f2));
	color: var(--brand-deep, var(--admin-brand-deep, #0f5e57));
	box-shadow: none;
}

.app-pagination .page-item.active .page-link {
	border-color: var(--brand, var(--admin-brand-deep, #0f766e));
	background: var(--brand, var(--admin-brand-deep, #0f766e));
	color: #ffffff;
}

.app-pagination .page-item.disabled .page-link {
	border-color: rgba(148, 163, 184, 0.16);
	background: #eef2f7;
	color: #94a3b8;
	pointer-events: none;
}

.app-pagination .page-item.disabled .page-link-ellipsis {
	min-width: 32px;
	border-color: transparent;
	background: transparent;
	color: #94a3b8;
}

@media (max-width: 575.98px) {
	.app-pagination {
		align-items: stretch;
	}

	.app-pagination nav {
		width: 100%;
		margin-left: 0;
	}

	.app-pagination .pagination {
		justify-content: flex-start;
	}

	.app-pagination .page-item .page-link {
		min-width: 38px;
		height: 38px;
	}
}
