# Open External Page As A Modal With ID
 This is a tutorial on how to open an external page within a modal with corresponding ID of the row record

![image](https://github.com/user-attachments/assets/b0ea80ec-2ff4-487b-bc3c-be3c4b45b41a)

The image above shows the table with rows of records. On the right is the button, when clicked will show **View Participants**. Now when you click on the **View Participants** it will load the pooup modal as seen below.
![image](https://github.com/user-attachments/assets/765ef3a4-dda1-4732-87e2-5d6ceb6420f3)

1. Download the entire folder
2. From the demo folder, open the index file index.html
3. Locate this 2 lines of code ```$.mask.definitions['h'] = "[A-Za-z0-9 ]";```	and ```$("#custominput").mask("ABC/aah/999/2029",{ autoclear: false });```
4. This line ```$.mask.definitions['h'] = "[A-Za-z0-9 ]``` sets your custom field variable while this line ```$("#custominput").mask("ABC/aah/999/2029",{ autoclear: false });``` sets the custom field itself.
5. So if you want to have an input mask for a school or office file number that is unique to the school or office alone, say something like this "ABC/DEC/123/2024", then use this line
```
$("#custominput").mask("ABC/aah/999/2029",{ autoclear: false });
```
6. If its "ABCX/DECX/1230/2024" with "X" and "0" being optional characters that can be replaced by a spacebar entry, then assign "h" in your script like this
```
$("#custominput").mask("ABCh/aaah/999h/2029",{ autoclear: false });
```
8. As you can see here => ```$.mask.definitions['h'] = "[A-Za-z0-9 ]";```, "h" represents an alphanumberic variable with a space after the 9. See ```"A-Za-z0-9 "```

```
<script type="text/javascript">
    $(function() {
        $.mask.definitions['~'] = "[+-]";
		$.mask.definitions['h'] = "[A-Za-z0-9 ]";		
		$("#custominput").mask("ABC/aah/999/2029",{ autoclear: false });	
        $("#date").mask("99/99/9999",{placeholder:"mm/dd/yyyy",completed:function(){alert("completed!");}});
        $(".phone").mask("(999) 999-9999");
        $("#phoneExt").mask("(999) 999-9999? x99999");
        $("#iphone").mask("+33 999 999 999");
        $("#tin").mask("99-9999999");
        $("#ssn").mask("999-99-9999");
        $("#product").mask("a*-999-a999", { placeholder: " " });
        $("#eyescript").mask("~9.99 ~9.99 999");
        $("#po").mask("PO: aaa-999-***",{autoclear: false});
        $("#pct").mask("99%");
        $("#phoneAutoclearFalse").mask("(999) 999-9999", { autoclear: false, completed:function(){alert("completed autoclear!");} });
        $("#phoneExtAutoclearFalse").mask("(999) 999-9999? x99999", { autoclear: false });
        $("input").blur(function() {
            $("#info").html("Unmasked value: " + $(this).mask());
        }).dblclick(function() {
            $(this).unmask();
        });
    });
</script>
```
When you done setting up your custom input mask, add it to your input box like this ```<input id="custominput" value="" type="text" tabindex="0" />```

Enjoy!!!
