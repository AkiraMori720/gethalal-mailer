function parseHash(hash) {
	hash = hash.substring(1, hash.length);

	var hashObj = [];

	hash.split('&').forEach(function (q) {
		if (typeof q !== 'undefined') {
			hashObj.push(q);
		}
	});

	return hashObj;
}

var gethmailer_urlHash = 'product';
var gethmailer_focusObj = false;
var gethmailer_urlHashArr = parseHash(window.location.hash);

if (gethmailer_urlHashArr[0] !== '') {
	gethmailer_urlHash = gethmailer_urlHashArr[0];
}

if (gethmailer_urlHashArr[1] !== "undefined") {
	gethmailer_focusObj = gethmailer_urlHashArr[1];
}

jQuery(function ($) {
	var gethmailer_activeTab = "";
	$('a.nav-tab').click(function (e) {
		if ($(this).attr('data-tab-name') !== gethmailer_activeTab) {
			$('div.gethmailer-tab-container').hide();
			$('a.nav-tab[data-tab-name="' + gethmailer_activeTab + '"]').removeClass('nav-tab-active');
			gethmailer_activeTab = $(this).attr('data-tab-name');
			$('div.gethmailer-tab-container[data-tab-name="' + gethmailer_activeTab + '"]').show();
			$(this).addClass('nav-tab-active');
			$('input#gethmailer-urlHash').val(gethmailer_activeTab);
			if (window.location.hash !== gethmailer_activeTab) {
				window.location.hash = gethmailer_activeTab;
			}
			if (gethmailer_focusObj) {
				$('html, body').animate({
					scrollTop: $('#' + gethmailer_focusObj).offset().top
				}, 'fast', function () {
					$('#' + gethmailer_focusObj).focus();
					gethmailer_focusObj = false;
				});
			}
		}
	});
	$('a.nav-tab[data-tab-name="' + gethmailer_urlHash + '"]').trigger('click');
});

jQuery(function ($) {
	$('#gethmailer-mail input').not('.ignore-change').change(function () {
		$('#gethmailer-save-settings-notice').show();
		$('#test-email-form-submit').prop('disabled', true);
	});
	$('#gethmailer_enable_domain_check').change(function () {
		$('input[name="gethmailer_allowed_domains"]').prop('disabled', !$(this).is(':checked'));
		$('input[name="gethmailer_block_all_emails"]').prop('disabled', !$(this).is(':checked'));
	});
	$('#gethmailer_clear_log_btn').click(function (e) {
		e.preventDefault();
		if (confirm(easywpsmtp.str.clear_log)) {
			var req = jQuery.ajax({
				url: ajaxurl,
				type: "post",
				data: { action: "gethmailer_clear_log", nonce: easywpsmtp.clear_log_nonce }
			});
			req.done(function (data) {
				if (data === '1') {
					alert(easywpsmtp.str.log_cleared);
				} else {
					alert(easywpsmtp.str.error_occured + ' ' + data);
				}
			});
		}
	});

	$('#gethmailer_export_settings_btn').click(function (e) {
		e.preventDefault();
		$('#gethmailer_export_settings_frm').submit();
	});

	$('#gethmailer_import_settings_btn').click(function (e) {
		e.preventDefault();
		$('#gethmailer_import_settings_select_file').click();
	});

	$('#gethmailer_import_settings_select_file').change(function (e) {
		e.preventDefault();
		$('#gethmailer_import_settings_frm').submit();
	});

	$('#gethmailer_self_destruct_btn').click(function (e) {
		e.preventDefault();
		if (confirm(easywpsmtp.str.confirm_self_destruct)) {
			var req = jQuery.ajax({
				url: ajaxurl,
				type: "post",
				data: { action: "gethmailer_self_destruct", sd_code: easywpsmtp.sd_code }
			});
			req.done(function (data) {
				if (data === '1') {
					alert(easywpsmtp.str.self_destruct_completed);
					window.location.href = easywpsmtp.sd_redir_url;
				} else {
					alert(easywpsmtp.str.error_occured + ' ' + data);
				}
			});
			req.fail(function (err) {
				alert(easywpsmtp.str.error_occured + ' ' + err.status + ' (' + err.statusText + ')');
			});
		}
	});

	$('#test-email-form-submit').click(function () {
		$(this).val(easywpsmtp.str.sending);
		$(this).prop('disabled', true);
		$('#gethmailer-spinner').addClass('is-active');
		$('#gethmailer_settings_test_email_form').submit();
		return true;
	});
});
