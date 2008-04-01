<?php

function addClassAttribute( $classID, $datatypeString )
{
    $objResponse = new xajaxResponse();

    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );

    $user =& eZUser::currentUser();
    $accessResult = $user->hasAccessTo( 'class', 'edit' );

    if ( $accessResult['accessWord'] == 'no' )
    {
        $objResponse->alert( 'You are not allowed to edit content classes' );
        return $objResponse;
    }

    include_once( 'kernel/classes/ezcontentclass.php' );
    $class = eZContentClass::fetch( $classID, true, EZ_CLASS_VERSION_STATUS_TEMPORARY );

    if ( !is_object( $class ) or $class->attribute( 'id' ) == null )
    {
        $objResponse->alert( 'Unable to find the temporary version of the class.' );
        return $objResponse;
    }
    else
    {
        include_once( 'lib/ezlocale/classes/ezdatetime.php' );

        include_once( 'lib/ezutils/classes/ezini.php' );
        $contentIni =& eZIni::instance( 'content.ini' );
        $timeOut = $contentIni->variable( 'ClassSettings', 'DraftTimeout' );

        if ( $class->attribute( 'modifier_id' ) != $user->attribute( 'contentobject_id' ) &&
             $class->attribute( 'modified' ) + $timeOut > time() )
        {
            $message = 'This class is already being edited by someone else.';
            $message = $message . ' The class is temporarly locked and thus it can not be edited by you.';

            $objResponse->alert( $message );
            return $objResponse;
        }
    }

    $existingAttributes =& eZContentClass::fetchAttributes( $classID, false, EZ_CLASS_VERSION_STATUS_TEMPORARY );

    $number = count( $existingAttributes ) + 1;

    include_once( 'kernel/classes/ezdatatype.php' );
    eZDataType::loadAndRegisterAllTypes();

    $new_attribute = eZContentClassAttribute::create( $classID, $datatypeString );
    $new_attribute->setAttribute( 'name', ezi18n( 'kernel/class/edit', 'new attribute' ) . $number );
    $dataType = $new_attribute->dataType();
    $dataType->initializeClassAttribute( $new_attribute );
    $new_attribute->store();

    include_once( 'kernel/common/template.php' );
    $tpl =& templateInit();

    $tpl->setVariable( 'attribute', $new_attribute );
    $tpl->setVariable( 'number', $number );

    $header1 =& $tpl->fetch( 'design:class/edit_ezxajax_attribute_header_1.tpl' );
    $header2 =& $tpl->fetch( 'design:class/edit_ezxajax_attribute_header_2.tpl' );
    $header3 =& $tpl->fetch( 'design:class/edit_ezxajax_attribute_header_3.tpl' );

    $cell2 =& $tpl->fetch( 'design:class/edit_ezxajax_attribute_cell_2.tpl' );

    $objResponse->call( 'addNewAttributeRows', $new_attribute->attribute( 'id' ) );

    $objResponse->assign( 'newHeader' . $new_attribute->attribute( 'id' ) . '_1', 'innerHTML', $header1 );
    $objResponse->assign( 'newHeader' . $new_attribute->attribute( 'id' ) . '_2', 'innerHTML', $header2 );
    $objResponse->assign( 'newHeader' . $new_attribute->attribute( 'id' ) . '_3', 'innerHTML', $header3 );

    $objResponse->assign( 'newCell' . $new_attribute->attribute( 'id' ) . '_2', 'innerHTML', $cell2 );

    return $objResponse;
}

function moveClassAttribute( $attributeID, $direction )
{
    $objResponse = new xajaxResponse();

    include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );

    $user = eZUser::currentUser();
    $accessResult = $user->hasAccessTo( 'class', 'edit' );

    if ( $accessResult['accessWord'] == 'no' )
    {
        $objResponse->alert( 'You are not allowed to edit content classes' );
        return $objResponse;
    }

    $attribute = eZContentClassAttribute::fetch( $attributeID, true, EZ_CLASS_VERSION_STATUS_TEMPORARY,
                                                  array( 'contentclass_id', 'version', 'placement' ) );

    if ( !$attribute )
    {
        $objResponse->alert( 'Unable to fetch the class attribute.' );
        return $objResponse;
    }

    $classID = $attribute->attribute( 'contentclass_id' );

    include_once( 'kernel/classes/ezcontentclass.php' );
    $class = eZContentClass::fetch( $classID, true, EZ_CLASS_VERSION_STATUS_TEMPORARY );

    if ( !is_object( $class ) or $class->attribute( 'id' ) == null )
    {
        $objResponse->alert( 'Unable to find the temporary version of the class.' );
        return $objResponse;
    }
    else
    {
        include_once( 'lib/ezlocale/classes/ezdatetime.php' );

        include_once( 'lib/ezutils/classes/ezini.php' );
        $contentIni = eZIni::instance( 'content.ini' );
        $timeOut = $contentIni->variable( 'ClassSettings', 'DraftTimeout' );

        if ( $class->attribute( 'modifier_id' ) != $user->attribute( 'contentobject_id' ) &&
             $class->attribute( 'modified' ) + $timeOut > time() )
        {
            $message = 'This class is already being edited by someone else.';
            $message = $message . ' The class is temporarly locked and thus it can not be edited by you.';

            $objResponse->alert( $message );
            return $objResponse;
        }
    }

    $attribute->move( $direction );
    $objResponse->call( 'moveAttributeRows', $attributeID, $direction );

    return $objResponse;

}

?>
