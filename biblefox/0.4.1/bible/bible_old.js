function bfox_get_context_parent(toggle)
{
	return jQuery(toggle).parents('.ref_seq').children('.ref_seq_body');
}

function bfox_get_context_chapters(parent)
{
	return parent.children('.visible_chapter');
}

function bfox_set_context_none(toggle)
{
	var parent = bfox_get_context_parent(toggle);
	var chapters = bfox_get_context_chapters(parent);
	chapters.children('.hidden_verses').hide();
	chapters.children('.hidden_verses_rule').show();
	parent.children('.hidden_chapter').hide();
}

function bfox_set_context_verses(toggle)
{
	var parent = bfox_get_context_parent(toggle);
	var chapters = bfox_get_context_chapters(parent);
	chapters.children('.hidden_verses').show();
	chapters.children('.hidden_verses_rule').hide();
	parent.children('.hidden_chapter').hide();
}

function bfox_set_context_chapters(toggle)
{
	var parent = bfox_get_context_parent(toggle);
	var chapters = bfox_get_context_chapters(parent);
	chapters.children('.hidden_verses').show();
	chapters.children('.hidden_verses_rule').hide();
	parent.children('.hidden_chapter').show();
}

function bfox_toggle_verse_paragraph()
{
	verse = 'Switch to Verse View';
	paragraph = 'Switch to Paragraph View';
	if (verse == jQuery('#verse_layout_toggle').html())
	{
		jQuery('.bible_verse').css('display', 'block').css('margin', '8px 0px 8px 0px');
		jQuery('.bible_end_p').css('display', 'none');
		jQuery('#verse_layout_toggle').html(paragraph);
	}
	else
	{
		jQuery('.bible_verse').css('display', 'inline').css('margin', '0px');
		jQuery('.bible_end_p').css('display', 'block');
		jQuery('#verse_layout_toggle').html(verse);
	}
}

function bfox_toggle_quick_view()
{
	if ('none' == jQuery('#bible_quick_view').css('display'))
	{
		jQuery('#bible_view').animate({width: '50%'}, 'fast').queue(function() {
			jQuery('#bible_quick_view').fadeIn('fast', function() {
				jQuery('#bible_view').dequeue();
			});
		});
	}
	else
	{
		jQuery('#bible_view').queue(function() {
			jQuery('#bible_quick_view').fadeOut('fast', function() {
				jQuery('#bible_view').dequeue();
			});
		}).animate({width: '100%'}, 'fast');
	}
}

function bfox_text_select(event)
{
	// Use Javascript Range Objects
	// See http://www.quirksmode.org/dom/range_intro.html
	var userSelection;
	if (window.getSelection) {
		userSelection = window.getSelection();
	}
	else if (document.selection) { // should come last; Opera!
		userSelection = document.selection.createRange();
	}
	
	var selectedText = userSelection;
	if (userSelection.text)
		selectedText = userSelection.text;

	var ref = '';
	
	if ('' != selectedText)
	{
		ref = jQuery('#bible_text_main_ref').html();
		var verse1 = jQuery(userSelection.anchorNode).parents('.bible_verse');
		var verse2 = jQuery(userSelection.focusNode).parents('.bible_verse');

		// You can only select within one book
		var book = verse1.attr('book');
		if (verse2.attr('book') == book) {
			var ch1 = eval(verse1.attr('chapter'));
			var ch2 = eval(verse2.attr('chapter'));
			var v1 = eval(verse1.attr('verse'));
			var v2 = eval(verse2.attr('verse'));
			if (ch1 > ch2) {
				ref = book + ' ' + ch2 + ':' + v2 + '-' + ch1 + ':' + v1;
			}
			else if (ch1 == ch2) {
				if (v1 > v2)
					ref = book + ' ' + ch2 + ':' + v2 + '-' + v1;
				else if (v1 == v2)
					ref = book + ' ' + ch1 + ':' + v1;
				else
					ref = book + ' ' + ch1 + ':' + v1 + '-' + v2;
			}
			else
				ref = book + ' ' + ch1 + ':' + v1 + '-' + ch2 + ':' + v2;
		}
	}
	
	if ('' != ref)
	{
		jQuery('#verse_selected').html(ref);
		jQuery('#quick_note_bible_ref').val(ref);
/*
		jQuery('#verse_select_menu').css('top', event.pageY + 'px').css('left', event.pageX + 'px').fadeIn('fast');
		*/
		jQuery('#verse_select_box').fadeIn('fast');
	}
	else if ('' == jQuery('#edit_quick_note_text').val())
		bfox_close_select_box();
}

function bfox_close_select_box() {
	jQuery('#verse_select_box').fadeOut('fast');
}

function bfox_save_quick_note()
{
	jQuery('.edit_quick_note_input').attr("disabled", true);

	var id = jQuery('#edit_quick_note_id').val();
	var note = jQuery('#edit_quick_note_text').val();
	var ref_str = jQuery('#quick_note_bible_ref').val();

	bfox_ajax_modify_note('Saving...', id, note, ref_str);
}

function bfox_delete_quick_note()
{
	jQuery('.edit_quick_note_input').attr("disabled", true);

	var id = jQuery('#edit_quick_note_id').val();

	bfox_ajax_modify_note('Deleting...', id, '', '');
}

function bfox_ajax_modify_note(msg, id, note, ref_str)
{
	var mysack = new sack(jQuery('#bible-request-url').val());
	
	mysack.execute = 1;
	mysack.method = 'POST';
	mysack.setVar("action", "bfox_ajax_save_quick_note");
	mysack.setVar("note", note);
	mysack.setVar("note_id", id);
	mysack.setVar("ref_str", ref_str);
	mysack.encVar("cookie", document.cookie, false);
	mysack.onError = function() { alert('Ajax error in saving the ')};
	mysack.runAJAX();

	jQuery('#edit_quick_note_progress').html(msg).fadeIn("fast");
	
	return false;
}

function bfox_quick_note_modified(msg, section_id, link, verse_id)
{
	if ('' != verse_id) jQuery(verse_id).remove();
	if ('' != section_id) jQuery(section_id).append(link);

	jQuery('#edit_quick_note_progress').fadeOut("fast", function() {
		jQuery('#edit_quick_note_progress').html(msg);
		jQuery('.edit_quick_note_input').removeAttr("disabled");
		bfox_new_quick_note();
	}).fadeIn(1000).fadeOut(1000, function() {
		bfox_close_select_box();
	});
}

function bfox_set_quick_note(id, note, ref)
{
	jQuery('#edit_quick_note_id').val(id);
	if ('' != ref) jQuery('#quick_note_bible_ref').val(ref);
	jQuery('#edit_quick_note_text').val(note).focus();
}

function bfox_edit_quick_note(id, note, ref)
{
	jQuery('#verse_select_box').fadeIn('fast');
	bfox_set_quick_note(id, note, ref);
}

function bfox_new_quick_note()
{
	bfox_set_quick_note('0', '', '');
}

function bfox_edit_quick_note_press_key( e ) {
	if ( 13 == e.keyCode ) {
		bfox_save_quick_note();
		return false;
	}
}

function bfox_note_popup_show(note) {
	jQuery(note).children('.note_popup').show();
}

function bfox_note_popup_hide(note) {
	jQuery(note).children('.note_popup').hide();
}

function bfox_select_quick_view(selected) {
	jQuery('.bible_quick_view_menu_option').hide();
	jQuery('#' + selected + '_header').show();
	jQuery('#' + selected + '_body').show();
}

jQuery(document).ready( function() {
	jQuery('#quick_view_button').click(bfox_toggle_quick_view);
	jQuery('#verse_layout_toggle').click(bfox_toggle_verse_paragraph);

	jQuery('#edit_quick_note_text').keypress(bfox_edit_quick_note_press_key).val('');

	jQuery('#bible_view_content').mouseup(bfox_text_select);
	bfox_select_quick_view('bible_quick_view_blogs');
});
