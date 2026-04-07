(function() {
	document.querySelectorAll('.aiprfohu-custom-models').forEach(function(container) {
		var hiddenId = container.dataset.hiddenId;
		var selectId = container.dataset.selectId;
		var hidden   = document.getElementById(hiddenId);
		var select   = document.getElementById(selectId);
		var input    = container.querySelector('.aiprfohu-custom-models__input');
		var addBtn   = container.querySelector('.aiprfohu-custom-models__add');
		var tagsWrap = container.querySelector('.aiprfohu-custom-models__tags');

		function getModels() {
			return hidden.value.split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
		}

		function setModels(models) {
			hidden.value = models.join('\n');
		}

		function syncSelect(models) {
			var optgroup = select.querySelector('optgroup[label="Custom Models"]');
			if (!optgroup) {
				optgroup = document.createElement('optgroup');
				optgroup.label = 'Custom Models';
				select.insertBefore(optgroup, select.options[1] || null);
			}

			while (optgroup.firstChild) {
				optgroup.removeChild(optgroup.firstChild);
			}

			models.forEach(function(modelId) {
				var parts = modelId.split('/');
				var name  = parts[parts.length - 1];
				var opt   = document.createElement('option');
				opt.value = modelId;
				opt.textContent = name + ' (' + modelId + ')';
				optgroup.appendChild(opt);
			});

			if (models.length === 0 && optgroup.parentNode) {
				optgroup.parentNode.removeChild(optgroup);
			}
		}

		function renderTags(models) {
			tagsWrap.innerHTML = '';
			models.forEach(function(modelId) {
				var tag = document.createElement('span');
				tag.className = 'aiprfohu-custom-models__tag';
				tag.textContent = modelId;

				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'aiprfohu-custom-models__remove';
				btn.dataset.model = modelId;
				btn.innerHTML = '&times;';
				tag.appendChild(btn);
				tagsWrap.appendChild(tag);
			});
		}

		function addModel() {
			var val = input.value.trim();
			if (!val) return;

			var models = getModels();
			if (models.indexOf(val) !== -1) {
				input.value = '';
				return;
			}

			models.push(val);
			setModels(models);
			renderTags(models);
			syncSelect(models);
			input.value = '';
			input.focus();
		}

		addBtn.addEventListener('click', addModel);
		input.addEventListener('keydown', function(e) {
			if (e.key === 'Enter') {
				e.preventDefault();
				addModel();
			}
		});

		tagsWrap.addEventListener('click', function(e) {
			var removeBtn = e.target.closest('.aiprfohu-custom-models__remove');
			if (!removeBtn) return;

			var modelId = removeBtn.dataset.model;
			var models  = getModels().filter(function(m) { return m !== modelId; });

			if (select.value === modelId) {
				select.value = '';
			}

			setModels(models);
			renderTags(models);
			syncSelect(models);
		});
	});
})();
