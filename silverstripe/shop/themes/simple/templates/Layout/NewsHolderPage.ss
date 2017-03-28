$Breadcrumbs
<% loop $Paginated() %>
        <h3>$date</h3>
        <ul>
            <% loop $items %>
                <li><a href="$Link">$Title</a></li>
            <% end_loop %>
        </ul>
        ++++++++++<br/>
<% end_loop %>

<% if $Paginated().MoreThanOnePage %>
    <% if $Paginated().NotFirstPage %>
        <a class="prev" href="$Paginated().PrevLink">Prev</a>
    <% end_if %>
    <% loop $Paginated().Pages %>
        <% if $CurrentBool %>
            $PageNum
        <% else %>
            <% if $Link %>
                <a href="$Link">$PageNum</a>
            <% else %>
                ...
            <% end_if %>
        <% end_if %>
    <% end_loop %>
    <% if $Paginated().NotLastPage %>
        <a class="next" href="$Paginated().NextLink">Next</a>
    <% end_if %>
<% end_if %>