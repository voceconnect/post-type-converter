jQuery(function(){
	
	var options = '<option value="-1">Convert Post Type</option>';
	for(var index in script_vars){
		options += '<option value="'+index.replace('_', '-')+'">'+script_vars[index]+'</option>';
	}
	var top_html = '<div class="alignleft actions"><select name="change_post_type">'+options+'</select></div><input type="submit" name="" id="apply_convert_post_type" class="button-secondary action" value="Convert">';
	var bottom_html = '<div class="alignleft actions"><select name="change_post_type2">'+options+'</select></div><input type="submit" name="" id="apply_convert_post_type" class="button-secondary action" value="Convert">';
	
	jQuery(top_html).insertBefore('.tablenav.top .tablenav-pages');
	jQuery(bottom_html).insertBefore('.tablenav.bottom .tablenav-pages');
});