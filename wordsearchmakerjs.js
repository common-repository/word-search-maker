function printwordsearchpuzzle(id)
{
   var html="<html><head><style type='text/css'>";
   html += "table.wsm_words, table.wsm_puzzle{border-collapse:collapse;margin:1em auto 1em auto;}" +
	"table.wsm_puzzle{border: 0.5em inset black;}table.wsm_words{border:0px;}" +
	"td.wsm_words, tr.wsm_words{text-align:left;padding:0.2em 0.7em 0.2em 0.7em;border:0px;vertical-align:top;}" +
	"td.wsm_puzzle, tr.wsm_puzzle{text-align:center;padding:2px;border:1px solid black;}" +
	"td.wsm_puzzle{width:1em;height:1em}";
   html += "</style></head><body>";
   html += document.getElementById(id).innerHTML;
   html += "</body></html>";
   var printWin = window.open('','','left=0,top=0,width=1,height=1,toolbar=0,scrollbars=0,status=0');
   printWin.document.write(html);
   printWin.document.close();
   printWin.focus();
   printWin.print();
   printWin.close();
}
function showhidewordsearchsolution()
{
	if(document.getElementById('wsm_solution_id').style.visibility == 'visible'){
		document.getElementById('wsm_solution_id').style.visibility = 'hidden';
	}else{
		document.getElementById('wsm_solution_id').style.visibility = 'visible';
	}
}