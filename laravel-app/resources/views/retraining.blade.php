@extends('layouts.app')

@section('content')
	@php
		$dataset = $dataset ?? null;
		$result = $result ?? null;
		$status = $status ?? 'Belum mulai';
		$pool = $pool ?? [
			'total_rows' => 0,
			'stroke_0' => 0,
			'stroke_1' => 0,
			'min_total_rows' => 50,
			'min_class_rows' => 10,
			'progress' => 0,
			'missing_messages' => [],
			'missing_models' => [],
			'data_ready' => false,
			'models_ready' => false,
			'training_in_progress' => false,
			'can_retrain' => false,
			'status_label' => 'Belum siap retraining',
		];
		$datasets = $datasets ?? collect();
		$canRetrain = (bool) ($pool['can_retrain'] ?? false);
		$poolMessages = $pool['missing_messages'] ?? [];
		$readinessMessages = $poolMessages;
		if (! ($pool['models_ready'] ?? false) && ! empty($pool['missing_models'] ?? [])) {
			$readinessMessages[] = 'Model belum tersedia: ' . implode(', ', $pool['missing_models']) . '.';
		}
		$activeInputMode = old('input_mode', 'upload');
		$summary = $dataset['summary'] ?? ['total_rows' => 0, 'valid_rows' => 0, 'stroke_0' => 0, 'stroke_1' => 0];
		$isDatasetValid = (bool) ($dataset['is_valid'] ?? false);
		$statusClass = match ($status) {
			'Selesai' => 'success',
			'Gagal', 'Validasi gagal' => 'danger',
			'Sedang training', 'Data siap, menunggu model' => 'warning',
			'Siap retraining' => 'ready',
			default => 'idle',
		};
		$formatPercent = function ($value) {
			if (! is_numeric($value)) {
				return '-';
			}
			$value = (float) $value;
			if ($value <= 1) {
				$value *= 100;
			}
			return number_format($value, 2) . '%';
		};
	@endphp

	<style>
		.retrain-hero {
			border-radius: 18px;
			border: 1px solid rgba(180, 83, 9, 0.28);
			background:
				radial-gradient(circle at top right, rgba(220, 38, 38, 0.18), transparent 34%),
				linear-gradient(135deg, rgba(245, 158, 11, 0.22), rgba(255, 255, 255, 0.96));
			padding: 1.5rem 1.75rem;
			box-shadow: 0 18px 36px rgba(15, 32, 50, 0.08);
		}

		.retrain-card {
			border-radius: 18px;
			border: 1px solid rgba(214, 226, 234, 0.95);
			background: #ffffff;
			padding: 1.5rem;
			box-shadow: 0 16px 32px rgba(15, 32, 50, 0.07);
			height: 100%;
		}

		.form-section-title {
			font-weight: 700;
			color: #0f172a;
			margin-bottom: 0.75rem;
		}

		.form-helper {
			color: #64748b;
			font-size: 0.85rem;
		}

		.retrain-card-head {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 1rem;
			margin-bottom: 1.25rem;
		}

		.retrain-footer {
			border-top: 1px solid rgba(148, 163, 184, 0.2);
			padding-top: 1.25rem;
		}

		.pool-card {
			position: relative;
			overflow: hidden;
			border-color: rgba(185, 28, 28, 0.18);
			background:
				radial-gradient(circle at top right, rgba(185, 28, 28, 0.14), transparent 34%),
				radial-gradient(circle at bottom left, rgba(245, 158, 11, 0.14), transparent 28%),
				linear-gradient(135deg, #fffaf4, #ffffff 58%);
		}

		.pool-card::after {
			content: "";
			position: absolute;
			right: -72px;
			top: -72px;
			width: 180px;
			height: 180px;
			border-radius: 50%;
			background: rgba(185, 28, 28, 0.08);
			pointer-events: none;
		}

		.pool-compact {
			padding: 1.35rem;
		}

		.pool-compact-head {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 1rem;
			margin-bottom: 1rem;
		}

		.pool-compact-stats {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 0.5rem;
			margin-bottom: 1.1rem;
		}

		.pool-compact-stat {
			position: relative;
			border-radius: 16px;
			border: 1px solid rgba(185, 28, 28, 0.1);
			background: rgba(255, 255, 255, 0.68);
			padding: 0.9rem 0.95rem;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72);
		}

		.pool-compact-stat .label {
			display: block;
			color: #7f1d1d;
			font-size: 0.68rem;
			font-weight: 900;
			text-transform: uppercase;
			letter-spacing: 0.08em;
			margin-bottom: 0.35rem;
		}

		.pool-compact-stat .value {
			color: #111827;
			font-size: 1.55rem;
			font-weight: 950;
			line-height: 1;
		}

		.pool-compact-stat .target {
			color: #64748b;
			font-size: 0.8rem;
			font-weight: 700;
		}

		.pool-compact-bottom {
			display: grid;
			grid-template-columns: minmax(0, 1fr) 260px;
			gap: 0;
			align-items: stretch;
			margin-top: 1.05rem;
			border-radius: 18px;
			border: 1px solid rgba(185, 28, 28, 0.13);
			background: rgba(255, 255, 255, 0.66);
			overflow: hidden;
		}

		.pool-readiness-note {
			border-radius: 0;
			border: 0;
			background: transparent;
			color: #7f1d1d;
			padding: 1rem 1.1rem;
			border-right: 1px solid rgba(185, 28, 28, 0.11);
		}

		.pool-readiness-note.is-ready {
			border-color: rgba(15, 118, 110, 0.16);
			background: rgba(240, 253, 250, 0.86);
			color: #0f766e;
		}

		.pool-action-compact {
			border-radius: 0;
			border: 0;
			background:
				radial-gradient(circle at top right, rgba(185, 28, 28, 0.1), transparent 34%),
				rgba(255, 255, 255, 0.5);
			padding: 1rem;
			box-shadow: none;
			display: flex;
			flex-direction: column;
			justify-content: center;
		}

		.readiness-list {
			display: grid;
			grid-template-columns: 1fr;
			gap: 0.5rem;
			margin: 0.75rem 0 0;
			padding: 0;
			list-style: none;
		}

		.readiness-chip {
			display: inline-flex;
			align-items: flex-start;
			gap: 0.5rem;
			border-radius: 12px;
			background: transparent;
			border: 0;
			color: #7f1d1d;
			padding: 0.12rem 0;
			font-size: 0.86rem;
			font-weight: 850;
			line-height: 1.4;
		}

		.readiness-chip i {
			color: #991b1b;
			margin-top: 0.18rem;
			font-size: 0.82rem;
			flex: 0 0 auto;
		}

		.readiness-title {
			display: flex;
			align-items: center;
			gap: 0.55rem;
			color: #7f1d1d;
			font-weight: 950;
			margin-bottom: 0.25rem;
		}

		.readiness-title i {
			width: 1.9rem;
			height: 1.9rem;
			border-radius: 12px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: #991b1b;
			color: #ffffff;
			flex: 0 0 auto;
		}

		.readiness-copy {
			color: #64748b;
			margin: 0;
			font-size: 0.92rem;
		}

		.retrain-action-title {
			color: #111827;
			font-weight: 950;
			margin-bottom: 0.25rem;
		}

		.retrain-action-state {
			color: #64748b;
			font-size: 0.84rem;
			line-height: 1.45;
			margin-bottom: 0.75rem;
		}

		.pool-progress-row {
			display: grid;
			grid-template-columns: minmax(0, 1fr) auto;
			gap: 0.8rem;
			align-items: center;
		}

		.pool-progress-label {
			color: #7f1d1d;
			font-size: 0.8rem;
			font-weight: 900;
			text-transform: uppercase;
			letter-spacing: 0.06em;
		}

		.pool-grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 0.85rem;
		}

		.pool-stat {
			border-radius: 18px;
			border: 1px solid rgba(185, 28, 28, 0.14);
			background: rgba(255, 255, 255, 0.8);
			padding: 1rem;
		}

		.pool-stat-label {
			color: #7f1d1d;
			font-size: 0.75rem;
			font-weight: 900;
			text-transform: uppercase;
			letter-spacing: 0.06em;
		}

		.pool-stat-value {
			color: #111827;
			font-size: 1.65rem;
			font-weight: 950;
			line-height: 1.1;
		}

		.pool-stat-target {
			color: #64748b;
			font-weight: 700;
			font-size: 0.85rem;
		}

		.pool-progress-track {
			height: 0.62rem;
			border-radius: 999px;
			background: rgba(185, 28, 28, 0.12);
			overflow: hidden;
		}

		.pool-progress-fill {
			height: 100%;
			border-radius: inherit;
			background: linear-gradient(90deg, #7f1d1d, #ef4444, #f97316);
			box-shadow: 0 8px 18px rgba(185, 28, 28, 0.22);
		}

		.pool-message-list {
			display: grid;
			gap: 0.55rem;
			margin: 0;
			padding: 0;
			list-style: none;
		}

		.pool-message {
			display: flex;
			gap: 0.55rem;
			align-items: flex-start;
			border-radius: 14px;
			border: 1px solid rgba(185, 28, 28, 0.14);
			background: rgba(254, 242, 242, 0.68);
			color: #7f1d1d;
			padding: 0.75rem 0.85rem;
			font-weight: 750;
		}

		.pool-message.is-ok {
			border-color: rgba(15, 118, 110, 0.16);
			background: rgba(240, 253, 250, 0.86);
			color: #0f766e;
		}

		.pool-action-panel {
			border-radius: 18px;
			border: 1px solid rgba(185, 28, 28, 0.14);
			background: rgba(255, 255, 255, 0.76);
			padding: 1rem;
		}

		.dataset-status-pill {
			display: inline-flex;
			align-items: center;
			border-radius: 999px;
			padding: 0.38rem 0.65rem;
			font-size: 0.78rem;
			font-weight: 900;
			white-space: nowrap;
		}

		.dataset-status-pill.is-valid {
			background: rgba(16, 185, 129, 0.12);
			color: #047857;
		}

		.dataset-status-pill.is-invalid {
			background: rgba(239, 68, 68, 0.12);
			color: #b91c1c;
		}

		.dataset-status-pill.is-used {
			background: rgba(14, 165, 233, 0.12);
			color: #0369a1;
		}

		.dataset-status-pill.is-archived {
			background: #f1f5f9;
			color: #475569;
		}

		.pool-table th,
		.pool-table td {
			vertical-align: middle;
			font-size: 0.88rem;
		}

		.pool-history-summary {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 1rem;
			border-radius: 16px;
			border: 1px solid rgba(185, 28, 28, 0.1);
			background: rgba(255, 255, 255, 0.62);
			padding: 0.85rem 1rem;
			margin-top: 1rem;
		}

		.pool-history-icon {
			width: 44px;
			height: 44px;
			border-radius: 14px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: rgba(15, 118, 110, 0.1);
			color: #0f766e;
			flex: 0 0 auto;
		}

		.pool-history-copy {
			min-width: 0;
		}

		.pool-history-title {
			color: #0f172a;
			font-weight: 900;
			margin-bottom: 0.1rem;
		}

		.pool-history-text {
			color: #64748b;
			margin: 0;
		}

		.pool-history-detail {
			border-radius: 18px;
			border: 1px solid rgba(214, 226, 234, 0.95);
			background: #ffffff;
			padding: 1rem;
			margin-top: 0.75rem;
			box-shadow: 0 12px 24px rgba(15, 32, 50, 0.05);
		}

		.status-pill {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
			border-radius: 999px;
			font-weight: 800;
			font-size: 0.8rem;
			padding: 0.5rem 0.78rem;
		}

		.status-pill.idle {
			background: #f1f5f9;
			color: #475569;
		}

		.status-pill.ready,
		.status-pill.success {
			background: rgba(16, 185, 129, 0.12);
			color: #047857;
		}

		.status-pill.warning {
			background: rgba(245, 158, 11, 0.14);
			color: #92400e;
		}

		.status-pill.danger {
			background: rgba(239, 68, 68, 0.12);
			color: #b91c1c;
		}

		.upload-zone {
			display: grid;
			place-items: center;
			border-radius: 18px;
			border: 2px dashed rgba(185, 28, 28, 0.42);
			background:
				radial-gradient(circle at top, rgba(185, 28, 28, 0.08), transparent 42%),
				linear-gradient(135deg, rgba(254, 242, 242, 0.96), rgba(255, 251, 235, 0.84));
			padding: 1.85rem 1.45rem;
			text-align: center;
			cursor: pointer;
			transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
		}

		.upload-zone:hover {
			border-color: rgba(153, 27, 27, 0.7);
			box-shadow: 0 18px 34px rgba(185, 28, 28, 0.13);
			transform: translateY(-1px);
		}

		.upload-zone input {
			position: absolute;
			width: 1px;
			height: 1px;
			opacity: 0;
			pointer-events: none;
		}

		.upload-icon-large {
			width: 58px;
			height: 58px;
			border-radius: 18px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: #a31818;
			color: #ffffff;
			font-size: 1.35rem;
			margin-bottom: 0.85rem;
			box-shadow: 0 16px 30px rgba(153, 27, 27, 0.28);
		}

		.file-name-badge {
			display: none;
			margin-top: 0.85rem;
			border-radius: 999px;
			background: rgba(15, 118, 110, 0.12);
			color: #0f5e57;
			font-weight: 900;
			font-size: 0.82rem;
			padding: 0.45rem 0.7rem;
			max-width: 100%;
			overflow-wrap: anywhere;
		}

		.file-name-badge.is-visible {
			display: inline-flex;
			align-items: center;
			gap: 0.45rem;
		}

		.form-control,
		.form-select {
			border-radius: 12px;
			border-color: rgba(148, 163, 184, 0.35);
			padding: 0.7rem 0.9rem;
		}

		.form-control:focus,
		.form-select:focus {
			box-shadow: 0 0 0 0.2rem rgba(185, 28, 28, 0.14);
			border-color: rgba(185, 28, 28, 0.62);
		}

		.input-mode-tabs {
			display: flex;
			gap: 0.35rem;
			border-radius: 22px;
			border: 1px solid rgba(185, 28, 28, 0.18);
			background: #fff7ed;
			padding: 0.35rem;
			margin-bottom: 1.1rem;
		}

		.mode-tab {
			flex: 1 1 0;
			display: flex;
			align-items: center;
			gap: 0.75rem;
			border: 1px solid transparent;
			border-radius: 18px;
			background: transparent;
			color: #7f1d1d;
			padding: 0.85rem 0.95rem;
			text-align: left;
			transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
		}

		.mode-tab:hover {
			border-color: rgba(185, 28, 28, 0.4);
			transform: translateY(-1px);
		}

		.mode-tab.is-active {
			border-color: rgba(153, 27, 27, 0.62);
			background:
				radial-gradient(circle at top right, rgba(185, 28, 28, 0.12), transparent 36%),
				#ffffff;
			box-shadow: 0 12px 24px rgba(185, 28, 28, 0.1);
		}

		.mode-tab-icon {
			width: 44px;
			height: 44px;
			border-radius: 16px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: rgba(185, 28, 28, 0.1);
			color: #991b1b;
			flex: 0 0 auto;
		}

		.mode-tab.is-active .mode-tab-icon {
			background: #a31818;
			color: #ffffff;
			box-shadow: 0 12px 24px rgba(153, 27, 27, 0.22);
		}

		.mode-tab-meta {
			min-width: 0;
		}

		.mode-tab-title {
			display: block;
			font-weight: 900;
			color: #111827;
			margin-bottom: 0.1rem;
		}

		.mode-tab-copy {
			display: block;
			color: #64748b;
			font-size: 0.86rem;
			line-height: 1.45;
		}

		.mode-panel {
			border-radius: 22px;
			border: 1px solid rgba(185, 28, 28, 0.12);
			background:
				linear-gradient(135deg, rgba(255, 247, 237, 0.66), rgba(255, 255, 255, 0.92));
			padding: 1rem;
		}

		.mode-panel-head {
			display: flex;
			align-items: center;
			gap: 0.8rem;
			margin-bottom: 1rem;
		}

		.mode-panel-icon {
			width: 46px;
			height: 46px;
			border-radius: 16px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: #a31818;
			color: #ffffff;
			box-shadow: 0 14px 24px rgba(153, 27, 27, 0.22);
			flex: 0 0 auto;
		}

		.mode-panel-title {
			color: #111827;
			font-weight: 900;
			margin: 0;
		}

		.mode-panel-copy {
			color: #64748b;
			margin: 0;
			line-height: 1.45;
		}

		.manual-form-grid {
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			gap: 0.9rem;
		}

		.manual-form-grid .full-span {
			grid-column: 1 / -1;
		}

		.manual-tip {
			border-radius: 16px;
			border: 1px solid rgba(185, 28, 28, 0.18);
			background: rgba(254, 242, 242, 0.72);
			color: #7f1d1d;
			padding: 0.85rem;
			font-weight: 700;
			line-height: 1.55;
		}

		.manual-section {
			border-radius: 18px;
			border: 1px solid rgba(148, 163, 184, 0.16);
			background: rgba(255, 255, 255, 0.82);
			padding: 1rem;
			margin-bottom: 1rem;
		}

		.manual-section:last-of-type {
			margin-bottom: 1rem;
		}

		.manual-section-title {
			display: flex;
			align-items: center;
			gap: 0.5rem;
			color: #7f1d1d;
			font-weight: 900;
			margin-bottom: 0.85rem;
		}

		.consent-card {
			display: grid;
			grid-template-columns: auto minmax(0, 1fr);
			gap: 0.85rem;
			align-items: flex-start;
			border-radius: 18px;
			border: 1px solid rgba(185, 28, 28, 0.24);
			background: #fff7ed;
			padding: 1rem;
			cursor: pointer;
			box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.65);
		}

		.consent-card:hover {
			border-color: rgba(185, 28, 28, 0.42);
		}

		.consent-checkbox {
			width: 1.45rem;
			height: 1.45rem;
			margin-top: 0.1rem;
			border: 2px solid #991b1b;
			cursor: pointer;
			flex-shrink: 0;
		}

		.consent-checkbox:checked {
			background-color: #991b1b;
			border-color: #991b1b;
		}

		.consent-title {
			display: block;
			color: #111827;
			font-weight: 900;
			margin-bottom: 0.25rem;
		}

		.consent-copy {
			color: #475569;
			line-height: 1.6;
			margin: 0;
		}

		.gate-modal .modal-content {
			border: 0;
			border-radius: 24px;
			overflow: hidden;
			box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
		}

		body.retraining-gate-active .page-frame > *:not(.gate-modal) {
			filter: blur(9px);
			opacity: 0.7;
			pointer-events: none;
			user-select: none;
			transition: filter 0.2s ease, opacity 0.2s ease;
		}

		body.retraining-gate-active .app-navbar {
			filter: blur(7px);
			opacity: 0.72;
			pointer-events: none;
			user-select: none;
			transition: filter 0.2s ease, opacity 0.2s ease;
		}

		.gate-modal-head {
			background:
				radial-gradient(circle at top right, rgba(245, 158, 11, 0.22), transparent 38%),
				linear-gradient(135deg, #7f1d1d, #991b1b);
			color: #ffffff;
			padding: 1.5rem;
		}

		.gate-icon {
			width: 54px;
			height: 54px;
			border-radius: 18px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: rgba(255, 255, 255, 0.14);
			border: 1px solid rgba(255, 255, 255, 0.22);
			margin-bottom: 0.85rem;
			font-size: 1.35rem;
		}

		.gate-rule {
			border-radius: 16px;
			background: #f8fafc;
			border: 1px solid rgba(148, 163, 184, 0.2);
			padding: 0.85rem;
			color: #334155;
			line-height: 1.55;
		}

		.model-status-panel {
			border-radius: 16px;
			border: 1px solid rgba(185, 28, 28, 0.14);
			background:
				radial-gradient(circle at top right, rgba(245, 158, 11, 0.14), transparent 42%),
				linear-gradient(135deg, rgba(254, 242, 242, 0.9), #ffffff);
			padding: 1rem;
		}

		.model-status-list {
			display: grid;
			gap: 0.75rem;
		}

		.model-status-item {
			display: flex;
			align-items: center;
			gap: 0.85rem;
			border-radius: 18px;
			border: 1px solid rgba(148, 163, 184, 0.18);
			background: #ffffff;
			padding: 0.9rem;
		}

		.model-status-item.is-ready {
			border-color: rgba(15, 118, 110, 0.18);
			background: rgba(240, 253, 250, 0.72);
		}

		.model-status-item.is-waiting {
			border-color: rgba(180, 83, 9, 0.2);
			background: rgba(255, 247, 237, 0.72);
			opacity: 0.82;
		}

		.model-status-icon {
			width: 42px;
			height: 42px;
			border-radius: 14px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: #0f766e;
			color: #ffffff;
			flex: 0 0 auto;
		}

		.model-status-item.is-waiting .model-status-icon {
			background: #92400e;
		}

		.model-status-title {
			color: #0f172a;
			font-weight: 900;
			line-height: 1.2;
		}

		.model-status-copy {
			color: #64748b;
			font-size: 0.86rem;
		}

		.model-grid {
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 0.75rem;
		}

		.model-choice {
			position: relative;
			border: 1px solid rgba(148, 163, 184, 0.28);
			border-radius: 16px;
			background: #f8fafc;
			padding: 1rem;
			min-height: 120px;
		}

		.model-choice input {
			position: absolute;
			inset: 0;
			opacity: 0;
			cursor: pointer;
		}

		.model-choice:has(input:checked) {
			border-color: rgba(15, 118, 110, 0.48);
			background: linear-gradient(135deg, rgba(223, 247, 242, 0.9), #ffffff);
			box-shadow: 0 12px 24px rgba(15, 118, 110, 0.12);
		}

		.model-choice.disabled {
			opacity: 0.65;
			cursor: not-allowed;
		}

		.model-choice.disabled input {
			cursor: not-allowed;
		}

		.metric-grid {
			display: grid;
			grid-template-columns: repeat(4, minmax(0, 1fr));
			gap: 0.75rem;
		}

		.metric-box,
		.summary-box {
			border-radius: 16px;
			border: 1px solid rgba(148, 163, 184, 0.22);
			background: #f8fafc;
			padding: 1rem;
		}

		.metric-label,
		.summary-label {
			color: #64748b;
			font-weight: 800;
			font-size: 0.72rem;
			text-transform: uppercase;
			letter-spacing: 0.06em;
		}

		.metric-value,
		.summary-value {
			color: #0f172a;
			font-size: 1.45rem;
			font-weight: 900;
		}

		.error-table td,
		.preview-table td,
		.preview-table th {
			white-space: nowrap;
			font-size: 0.85rem;
		}

		@media (max-width: 991.98px) {
			.input-mode-tabs {
				flex-direction: column;
			}

			.pool-history-summary {
				align-items: stretch;
				flex-direction: column;
			}

			.pool-compact-head,
			.pool-compact-bottom {
				grid-template-columns: 1fr;
			}

			.pool-readiness-note {
				border-right: 0;
				border-bottom: 1px solid rgba(185, 28, 28, 0.11);
			}

			.pool-compact-head {
				display: grid;
			}

			.pool-compact-stats,
			.manual-form-grid,
			.model-grid,
			.pool-grid,
			.metric-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<div class="retrain-hero mb-4">
		<div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
			<div>
				<p class="eyebrow mb-2">Retraining Model</p>
				<h1 class="h4 fw-bold mb-2"><i class="fa-solid fa-rotate me-2"></i>Latih ulang model dari dataset baru.</h1>
				<p class="text-muted mb-0">
					Unggah dataset sesuai template, validasi isi file, lalu kumpulkan data valid sampai retraining penuh siap dijalankan.
				</p>
			</div>
			<span class="status-pill {{ $statusClass }}">
				<i class="fa-solid {{ $statusClass === 'danger' ? 'fa-circle-exclamation' : ($statusClass === 'success' ? 'fa-circle-check' : 'fa-clock') }}"></i>
				{{ $status }}
			</span>
		</div>
	</div>

	<div class="modal fade gate-modal" id="retrainingGateModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
		<div class="modal-dialog modal-lg modal-dialog-centered">
			<div class="modal-content">
				<div class="gate-modal-head">
					<div class="gate-icon">
						<i class="fa-solid fa-user-doctor"></i>
					</div>
					<h2 class="h4 fw-bold mb-2">Peringatan sebelum masuk menu Retraining</h2>
					<p class="mb-0 text-white-50">
						Data yang diupload di halaman ini akan dipakai untuk melatih ulang model. Pastikan label stroke berasal dari sumber medis atau dataset kesehatan terpercaya.
					</p>
				</div>
				<div class="modal-body p-4">
					<div class="row g-3 mb-3">
						<div class="col-md-6">
							<div class="gate-rule h-100">
								<div class="fw-bold text-success mb-1"><i class="fa-solid fa-circle-check me-1"></i>Data yang boleh dipakai</div>
								Diagnosis dokter, rekam medis, data rumah sakit, hasil pemeriksaan medis, atau dataset kesehatan resmi/terpercaya.
							</div>
						</div>
						<div class="col-md-6">
							<div class="gate-rule h-100">
								<div class="fw-bold text-danger mb-1"><i class="fa-solid fa-circle-xmark me-1"></i>Data yang tidak boleh dipakai</div>
								Data dummy, asal isi, hasil tebakan, atau hasil prediksi dari website ini sebagai label <code>stroke</code>.
							</div>
						</div>
					</div>
					<div class="alert alert-warning mb-0">
						<i class="fa-solid fa-triangle-exclamation me-1"></i>
						Jika label stroke tidak benar, model baru bisa belajar pola yang salah dan kualitas prediksi berikutnya bisa rusak.
					</div>
				</div>
				<div class="modal-footer p-4 pt-0 border-0">
					<button type="button" class="btn btn-dark btn-lg w-100" id="confirmRetrainingGate">
						Saya paham, lanjut ke form retraining
					</button>
				</div>
			</div>
		</div>
	</div>

	@if(session('success'))
		<div class="alert alert-success">{{ session('success') }}</div>
	@endif

	@if($errors->any())
		<div class="alert alert-danger">
			<ul class="mb-0">
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<div class="retrain-card pool-card pool-compact mb-4">
		<div class="pool-compact-head">
			<div>
				<p class="eyebrow mb-2">Pool Data Retraining</p>
				<h2 class="h5 fw-bold mb-1">Progress data retraining</h2>
				<p class="form-helper mb-0">
					Data valid dari upload dan input manual dikumpulkan dulu. Retraining aktif jika syarat data dan model sudah lengkap.
				</p>
			</div>
			<span class="status-pill {{ $statusClass }}">
				<i class="fa-solid {{ $canRetrain ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
				{{ $pool['status_label'] ?? $status }}
			</span>
		</div>

		<div class="pool-compact-stats">
			<div class="pool-compact-stat">
				<span class="label">Total valid</span>
				<span class="value">{{ $pool['total_rows'] ?? 0 }}</span>
				<span class="target">/ {{ $pool['min_total_rows'] ?? 50 }} data</span>
			</div>
			<div class="pool-compact-stat">
				<span class="label">Stroke = 0</span>
				<span class="value">{{ $pool['stroke_0'] ?? 0 }}</span>
				<span class="target">/ {{ $pool['min_class_rows'] ?? 10 }} data</span>
			</div>
			<div class="pool-compact-stat">
				<span class="label">Stroke = 1</span>
				<span class="value text-danger">{{ $pool['stroke_1'] ?? 0 }}</span>
				<span class="target">/ {{ $pool['min_class_rows'] ?? 10 }} data</span>
			</div>
		</div>

		<div>
			<div class="pool-progress-row mb-2">
				<span class="pool-progress-label">Progress pool</span>
				<span class="fw-bold">{{ $pool['progress'] ?? 0 }}%</span>
			</div>
			<div class="pool-progress-track" aria-label="Progress pool data retraining">
				<div class="pool-progress-fill" style="width: {{ $pool['progress'] ?? 0 }}%"></div>
			</div>
		</div>

		<div class="pool-history-summary">
			<div class="d-flex align-items-center gap-3">
				<span class="pool-history-icon">
					<i class="fa-solid fa-clock-rotate-left"></i>
				</span>
				<div class="pool-history-copy">
					<div class="pool-history-title">Riwayat pool</div>
					<p class="pool-history-text">
						{{ $datasets->count() }} input tercatat untuk bahan retraining.
					</p>
				</div>
			</div>
			<button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#poolHistoryCollapse" aria-expanded="false" aria-controls="poolHistoryCollapse" @disabled($datasets->isEmpty())>
				<i class="fa-solid fa-list-check me-2"></i>{{ $datasets->isEmpty() ? 'Belum ada riwayat' : 'Lihat Riwayat' }}
			</button>
		</div>

		<div class="pool-compact-bottom">
			<div class="pool-readiness-note {{ $canRetrain ? 'is-ready' : '' }}">
				@if($canRetrain)
					<div class="readiness-title">
						<i class="fa-solid fa-circle-check"></i>
						<span>Siap retraining</span>
					</div>
					<p class="readiness-copy">Data dan semua model sudah lengkap. Retraining penuh bisa dijalankan.</p>
				@else
					<div class="readiness-title">
						<i class="fa-solid fa-lock"></i>
						<span>Belum bisa retraining</span>
					</div>
					<p class="readiness-copy">Lengkapi syarat berikut agar tombol retraining aktif.</p>
					<ul class="readiness-list">
						@forelse($readinessMessages as $message)
							<li class="readiness-chip">
								<i class="fa-solid fa-circle-xmark"></i>
								<span>{{ $message }}</span>
							</li>
						@empty
							<li class="readiness-chip">
								<i class="fa-solid fa-circle-xmark"></i>
								<span>Lengkapi data dan model terlebih dahulu.</span>
							</li>
						@endforelse
					</ul>
				@endif
			</div>
			<div class="pool-action-compact">
				<div class="retrain-action-title">{{ $canRetrain ? 'Aksi tersedia' : 'Terkunci sementara' }}</div>
				<div class="retrain-action-state">{{ $canRetrain ? 'Model siap dilatih ulang.' : 'Belum bisa dijalankan karena syarat belum lengkap.' }}</div>
				<form action="{{ route('retraining.start') }}" method="POST">
					@csrf
					<button type="submit" class="btn btn-dark w-100 py-2" @disabled(! $canRetrain)>
						<i class="fa-solid fa-rotate me-2"></i>Mulai Retraining
					</button>
				</form>
			</div>
		</div>
	</div>

	<div class="row g-4">
		<div class="col-12">
			<div class="retrain-card" id="uploadRetrainingCard">
				<div class="retrain-card-head">
					<div>
						<h6 class="form-section-title"><i class="fa-solid fa-database me-2"></i>Input Dataset Retraining</h6>
						<p class="form-helper mb-0">Pilih upload file atau isi satu data diagnosis secara manual.</p>
					</div>
					<a href="{{ asset('templates/stroke-retraining-template.csv') }}" class="btn btn-outline-secondary" download>
						<i class="fa-solid fa-file-csv me-2"></i>Download Template
					</a>
				</div>

				<div class="input-mode-tabs" role="tablist" aria-label="Pilihan input retraining">
					<button type="button" class="mode-tab {{ $activeInputMode !== 'manual' ? 'is-active' : '' }}" data-mode-tab="upload">
						<span class="mode-tab-icon"><i class="fa-solid fa-file-arrow-up"></i></span>
						<span class="mode-tab-meta">
							<span class="mode-tab-title">Upload File</span>
							<span class="mode-tab-copy">Banyak data lewat CSV/XLSX.</span>
						</span>
					</button>
					<button type="button" class="mode-tab {{ $activeInputMode === 'manual' ? 'is-active' : '' }}" data-mode-tab="manual">
						<span class="mode-tab-icon"><i class="fa-solid fa-pen-to-square"></i></span>
						<span class="mode-tab-meta">
							<span class="mode-tab-title">Isi Manual</span>
							<span class="mode-tab-copy">Satu data berlabel asli.</span>
						</span>
					</button>
				</div>

				<div class="mode-panel {{ $activeInputMode === 'manual' ? 'd-none' : '' }}" data-mode-panel="upload">
					<div class="mode-panel-head">
						<span class="mode-panel-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
						<div>
							<h3 class="mode-panel-title h6">Upload dataset diagnosis</h3>
							<p class="mode-panel-copy">Gunakan template agar header file terbaca dengan benar.</p>
						</div>
					</div>
					<form action="{{ route('retraining.upload') }}" method="POST" enctype="multipart/form-data">
						@csrf
						<input type="hidden" name="input_mode" value="upload">
						<div class="mb-3">
							<label class="upload-zone" for="retraining_file">
								<input id="retraining_file" type="file" name="retraining_file" accept=".csv,.txt,.xlsx" required data-gated-control>
								<span>
									<span class="upload-icon-large"><i class="fa-solid fa-file-medical"></i></span>
									<span class="h5 fw-bold d-block mb-2">Pilih dataset diagnosis asli</span>
									<span class="text-muted d-block">Format CSV/XLSX, maksimal 5 MB dan 5.000 baris.</span>
									<span class="file-name-badge" id="retrainingFileName">
										<i class="fa-solid fa-file-lines"></i>
										<span></span>
									</span>
								</span>
							</label>
							<div class="form-text mt-2">CSV lebih disarankan untuk dataset besar karena lebih ringan diproses.</div>
						</div>
						<label class="consent-card mb-3" for="upload_data_consent">
							<input class="form-check-input consent-checkbox" id="upload_data_consent" type="checkbox" name="data_consent" value="1" required data-gated-control>
							<span>
								<span class="consent-title">Saya bertanggung jawab atas sumber data ini</span>
								<p class="consent-copy">
									Label <code>stroke</code> pada file ini berasal dari diagnosis/pemeriksaan medis, rekam medis, rumah sakit, dokter, atau dataset kesehatan terpercaya. Data ini bukan hasil prediksi website dan bukan data asal isi.
								</p>
							</span>
						</label>
						<div class="retrain-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
							<p class="mb-0 text-muted">Data valid akan disiapkan untuk proses retraining.</p>
							<button type="submit" class="btn btn-dark px-4" data-gated-control>
								<i class="fa-solid fa-shield-check me-2"></i>Upload & Validasi
							</button>
						</div>
					</form>
				</div>

				<div class="mode-panel {{ $activeInputMode === 'manual' ? '' : 'd-none' }}" data-mode-panel="manual">
					<div class="mode-panel-head">
						<span class="mode-panel-icon"><i class="fa-solid fa-clipboard-list"></i></span>
						<div>
							<h3 class="mode-panel-title h6">Isi satu data diagnosis</h3>
							<p class="mode-panel-copy">Cocok untuk menambahkan satu baris data retraining tanpa membuat file CSV.</p>
						</div>
					</div>
					<form action="{{ route('retraining.manual') }}" method="POST">
						@csrf
						<input type="hidden" name="input_mode" value="manual">
						<div class="manual-tip mb-3">
							<i class="fa-solid fa-triangle-exclamation me-1"></i>
							Input manual tetap wajib punya label <code>stroke</code> asli dari diagnosis/sumber terpercaya.
						</div>
						<div class="manual-section">
							<div class="manual-section-title"><i class="fa-solid fa-user"></i>Data Pasien</div>
							<div class="manual-form-grid">
								<div>
									<label class="form-label" for="manual_gender">Gender</label>
									<select class="form-select" id="manual_gender" name="gender" required data-gated-control>
										@foreach(['Male' => 'Male', 'Female' => 'Female', 'Other' => 'Other'] as $value => $label)
											<option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
										@endforeach
									</select>
								</div>
								<div>
									<label class="form-label" for="manual_age">Age</label>
									<input class="form-control" id="manual_age" type="number" name="age" min="0" max="120" step="0.1" value="{{ old('age') }}" required data-gated-control>
								</div>
								<div>
									<label class="form-label" for="manual_ever_married">Ever Married</label>
									<select class="form-select" id="manual_ever_married" name="ever_married" required data-gated-control>
										<option value="Yes" @selected(old('ever_married') === 'Yes')>Yes</option>
										<option value="No" @selected(old('ever_married') === 'No')>No</option>
									</select>
								</div>
								<div>
									<label class="form-label" for="manual_work_type">Work Type</label>
									<select class="form-select" id="manual_work_type" name="work_type" required data-gated-control>
										@foreach(['Private', 'Self-employed', 'Govt_job', 'children', 'Never_worked'] as $value)
											<option value="{{ $value }}" @selected(old('work_type') === $value)>{{ $value }}</option>
										@endforeach
									</select>
								</div>
								<div class="full-span">
									<label class="form-label" for="manual_residence_type">Residence Type</label>
									<select class="form-select" id="manual_residence_type" name="Residence_type" required data-gated-control>
										<option value="Urban" @selected(old('Residence_type') === 'Urban')>Urban</option>
										<option value="Rural" @selected(old('Residence_type') === 'Rural')>Rural</option>
									</select>
								</div>
							</div>
						</div>

						<div class="manual-section">
							<div class="manual-section-title"><i class="fa-solid fa-notes-medical"></i>Kondisi Medis</div>
							<div class="manual-form-grid">
								<div>
									<label class="form-label" for="manual_hypertension">Hypertension</label>
									<select class="form-select" id="manual_hypertension" name="hypertension" required data-gated-control>
										<option value="0" @selected(old('hypertension') === '0')>0 - Tidak</option>
										<option value="1" @selected(old('hypertension') === '1')>1 - Ya</option>
									</select>
								</div>
								<div>
									<label class="form-label" for="manual_heart_disease">Heart Disease</label>
									<select class="form-select" id="manual_heart_disease" name="heart_disease" required data-gated-control>
										<option value="0" @selected(old('heart_disease') === '0')>0 - Tidak</option>
										<option value="1" @selected(old('heart_disease') === '1')>1 - Ya</option>
									</select>
								</div>
								<div>
									<label class="form-label" for="manual_avg_glucose_level">Avg Glucose Level</label>
									<input class="form-control" id="manual_avg_glucose_level" type="number" name="avg_glucose_level" min="40" max="400" step="0.01" value="{{ old('avg_glucose_level') }}" required data-gated-control>
								</div>
								<div>
									<label class="form-label" for="manual_bmi">BMI</label>
									<input class="form-control" id="manual_bmi" type="number" name="bmi" min="10" max="80" step="0.01" value="{{ old('bmi') }}" required data-gated-control>
								</div>
								<div class="full-span">
									<label class="form-label" for="manual_smoking_status">Smoking Status</label>
									<select class="form-select" id="manual_smoking_status" name="smoking_status" required data-gated-control>
										@foreach(['formerly smoked', 'never smoked', 'smokes', 'Unknown'] as $value)
											<option value="{{ $value }}" @selected(old('smoking_status') === $value)>{{ $value }}</option>
										@endforeach
									</select>
								</div>
							</div>
						</div>

						<div class="manual-section">
							<div class="manual-section-title"><i class="fa-solid fa-stethoscope"></i>Label Diagnosis</div>
							<div>
								<label class="form-label text-danger" for="manual_stroke">Label Stroke Asli</label>
								<select class="form-select border-danger" id="manual_stroke" name="stroke" required data-gated-control>
									<option value="0" @selected(old('stroke') === '0')>0 - Tidak stroke</option>
									<option value="1" @selected(old('stroke') === '1')>1 - Stroke</option>
								</select>
								<div class="form-text">Ini harus label asli, bukan hasil prediksi sistem.</div>
							</div>
						</div>
						<label class="consent-card mb-3" for="manual_data_consent">
							<input class="form-check-input consent-checkbox" id="manual_data_consent" type="checkbox" name="data_consent" value="1" required data-gated-control>
							<span>
								<span class="consent-title">Saya bertanggung jawab atas sumber data manual ini</span>
								<p class="consent-copy">
									Label <code>stroke</code> yang saya isi berasal dari diagnosis/pemeriksaan medis, rekam medis, rumah sakit, dokter, atau dataset kesehatan terpercaya.
								</p>
							</span>
						</label>
						<div class="retrain-footer d-flex flex-wrap justify-content-between align-items-center gap-3">
							<p class="mb-0 text-muted">Data manual akan disimpan sebagai satu baris dataset retraining.</p>
							<button type="submit" class="btn btn-dark px-4" data-gated-control>
								<i class="fa-solid fa-floppy-disk me-2"></i>Simpan & Validasi
							</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	@if($dataset)
		<div class="retrain-card mt-4">
			<div class="retrain-card-head">
				<div>
					<h6 class="form-section-title"><i class="fa-solid fa-table me-2"></i>Input Terakhir</h6>
					<p class="form-helper mb-0">
						{{ $dataset['uploaded_name'] ?? '-' }} · status: <strong>{{ $dataset['status'] ?? '-' }}</strong>
					</p>
				</div>
				<span class="status-pill {{ $isDatasetValid ? 'success' : 'danger' }}">
					<i class="fa-solid {{ $isDatasetValid ? 'fa-circle-check' : 'fa-circle-exclamation' }}"></i>
					{{ $isDatasetValid ? 'Masuk pool' : 'Tidak masuk pool' }}
				</span>
			</div>

			<div class="pool-compact-stats mb-3">
				<div class="pool-compact-stat">
					<span class="label">Total baris</span>
					<span class="value">{{ $summary['total_rows'] ?? 0 }}</span>
				</div>
				<div class="pool-compact-stat">
					<span class="label">Stroke = 0</span>
					<span class="value">{{ $summary['stroke_0'] ?? 0 }}</span>
				</div>
				<div class="pool-compact-stat">
					<span class="label">Stroke = 1</span>
					<span class="value text-danger">{{ $summary['stroke_1'] ?? 0 }}</span>
				</div>
			</div>

			<div class="table-responsive">
				<table class="table table-sm preview-table align-middle">
					<thead>
						<tr>
							@foreach(['gender','age','hypertension','heart_disease','ever_married','work_type','Residence_type','avg_glucose_level','bmi','smoking_status','stroke'] as $column)
								<th>{{ $column }}</th>
							@endforeach
						</tr>
					</thead>
					<tbody>
						@foreach(($dataset['preview'] ?? []) as $row)
							<tr>
								@foreach(['gender','age','hypertension','heart_disease','ever_married','work_type','Residence_type','avg_glucose_level','bmi','smoking_status','stroke'] as $column)
									<td>{{ $row[$column] ?? '-' }}</td>
								@endforeach
							</tr>
						@endforeach
					</tbody>
				</table>
			</div>

			@if(! $isDatasetValid)
				<hr>
				<h6 class="form-section-title text-danger"><i class="fa-solid fa-circle-exclamation me-2"></i>Validasi Gagal</h6>
				<div class="table-responsive">
					<table class="table table-sm error-table mb-0">
						<thead>
							<tr>
								<th>Baris</th>
								<th>Kolom</th>
								<th>Alasan</th>
							</tr>
						</thead>
						<tbody>
							@foreach(($dataset['errors'] ?? []) as $error)
								<tr>
									<td>{{ $error['row'] }}</td>
									<td>{{ $error['column'] }}</td>
									<td>{{ $error['message'] }}</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			@endif
		</div>
	@endif

	@if(! $datasets->isEmpty())
		<div class="collapse" id="poolHistoryCollapse">
			<div class="pool-history-detail">
				<div class="table-responsive">
					<table class="table pool-table align-middle mb-0">
						<thead>
							<tr>
								<th>Sumber</th>
								<th>Tanggal</th>
								<th>Valid</th>
								<th>Tidak Stroke</th>
								<th>Stroke</th>
								<th>Status</th>
								<th class="text-end">Aksi</th>
							</tr>
						</thead>
						<tbody>
							@foreach($datasets as $item)
								@php
									$itemStatusClass = match ($item->status) {
										'Valid' => 'is-valid',
										'Invalid' => 'is-invalid',
										'Used for Retraining' => 'is-used',
										'Archived' => 'is-archived',
										default => 'is-archived',
									};
								@endphp
								<tr>
									<td>
										<div class="fw-bold text-dark">{{ $item->source_type === 'manual' ? 'Input manual' : $item->source_name }}</div>
										<div class="text-muted small">{{ $item->source_type === 'manual' ? $item->source_name : 'Upload file' }}</div>
									</td>
									<td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
									<td>{{ $item->valid_rows }}</td>
									<td>{{ $item->stroke_0 }}</td>
									<td>{{ $item->stroke_1 }}</td>
									<td>
										<span class="dataset-status-pill {{ $itemStatusClass }}">{{ $item->status }}</span>
									</td>
									<td class="text-end">
										@if(in_array($item->status, ['Valid', 'Invalid'], true))
											<form action="{{ route('retraining.archive', $item) }}" method="POST" class="d-inline">
												@csrf
												<button type="submit" class="btn btn-sm btn-outline-secondary">
													<i class="fa-solid fa-box-archive me-1"></i>Archive
												</button>
											</form>
										@else
											<span class="text-muted small">-</span>
										@endif
									</td>
								</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	@endif

	@if($result)
		<div class="retrain-card mt-4">
			<h6 class="form-section-title"><i class="fa-solid fa-square-poll-vertical me-2"></i>Hasil Retraining</h6>
			@if(($result['status'] ?? null) === 'error')
				<div class="alert alert-danger mb-0">{{ $result['message'] ?? 'Retraining gagal.' }}</div>
			@else
				<p class="text-muted">Backup model lama: <code>{{ $result['backup_dir'] ?? '-' }}</code></p>
				@foreach(($result['models'] ?? []) as $modelKey => $modelResult)
					@php
						$metrics = $modelResult['metrics'] ?? [];
						$strokeMetrics = $metrics['classification_report']['1'] ?? [];
						$cm = $metrics['confusion_matrix'] ?? [[0, 0], [0, 0]];
						$falseNegative = $cm[1][0] ?? 0;
					@endphp
					<div class="border rounded-4 p-3 mb-3">
						<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
							<h3 class="h6 fw-bold mb-0">{{ $modelResult['model_name'] ?? $modelKey }}</h3>
							<span class="status-pill success"><i class="fa-solid fa-circle-check"></i>Berhasil</span>
						</div>
						<div class="metric-grid">
							<div class="metric-box">
								<div class="metric-label">Accuracy</div>
								<div class="metric-value">{{ $formatPercent($metrics['accuracy'] ?? null) }}</div>
							</div>
							<div class="metric-box">
								<div class="metric-label">Recall Stroke</div>
								<div class="metric-value text-success">{{ $formatPercent($strokeMetrics['recall'] ?? null) }}</div>
							</div>
							<div class="metric-box">
								<div class="metric-label">F1 Stroke</div>
								<div class="metric-value">{{ $formatPercent($strokeMetrics['f1-score'] ?? null) }}</div>
							</div>
							<div class="metric-box">
								<div class="metric-label">False Negative</div>
								<div class="metric-value text-danger">{{ $falseNegative }}</div>
							</div>
						</div>
					</div>
				@endforeach
			@endif
		</div>
	@endif

	<script>
		window.addEventListener('DOMContentLoaded', () => {
			const gateModalElement = document.getElementById('retrainingGateModal');
			const confirmGateButton = document.getElementById('confirmRetrainingGate');
			const fileInput = document.getElementById('retraining_file');
			const fileNameBadge = document.getElementById('retrainingFileName');
			const fileNameText = fileNameBadge?.querySelector('span');
			const modeTabs = document.querySelectorAll('[data-mode-tab]');
			const modePanels = document.querySelectorAll('[data-mode-panel]');
			const setGateFocus = (active) => {
				document.body.classList.toggle('retraining-gate-active', active);
			};

			const activateMode = (mode) => {
				modeTabs.forEach((tab) => {
					tab.classList.toggle('is-active', tab.dataset.modeTab === mode);
				});

				modePanels.forEach((panel) => {
					panel.classList.toggle('d-none', panel.dataset.modePanel !== mode);
				});
			};

			if (gateModalElement && window.bootstrap) {
				const modal = new bootstrap.Modal(gateModalElement, {
					backdrop: 'static',
					keyboard: false,
				});
				setGateFocus(true);
				modal.show();

				confirmGateButton?.addEventListener('click', () => {
					setGateFocus(false);
					modal.hide();
				});
			} else {
				setGateFocus(false);
			}

			modeTabs.forEach((tab) => {
				tab.addEventListener('click', () => {
					activateMode(tab.dataset.modeTab);
				});
			});

			fileInput?.addEventListener('change', () => {
				const file = fileInput.files?.[0];
				if (!file || !fileNameBadge || !fileNameText) return;
				fileNameText.textContent = file.name;
				fileNameBadge.classList.add('is-visible');
			});
		});
	</script>
@endsection
